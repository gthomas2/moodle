<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * HTML responsive images filter.
 *
 * @package    filter
 * @subpackage responsive
 * @copyright  2015 Guy Thomas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/gdlib.php');

// This class looks for text including img tags and makes any images responsive using the srcset attribute.

class filter_responsive extends moodle_text_filter {

    /**
     * Shame that this was nicked from gdlib.php and that there isn't a function I could have used from there.
     * Creates a resized version of image and stores copy in file area
     *
     * @param context $context
     * @param string $component
     * @param string filearea
     * @param int $itemid
     * @param string $originalfile
     * @param int $newwidth;
     * @param int $newheight;
     * @return mixed new unique revision number or false if not saved
     */
    function copy_resize_image($context, $component, $filearea, $itemid, $originalfile, $resizefilename, $newwidth = false, $newheight = false) {

        if (!$newwidth && !$newheight) {
            return false;
        }

        if (!is_file($originalfile)) {
            return false;
        }

        $imageinfo = getimagesize($originalfile);
        $imagefnc = '';

        if (empty($imageinfo)) {
            return false;
        }

        $image = new stdClass();
        $image->width  = $imageinfo[0];
        $image->height = $imageinfo[1];
        $image->type   = $imageinfo[2];

        if (!$newheight) {
            $m = $image->height / $image->width; // Multiplier to work out $newheight.
            $newheight = $newwidth * $m;
        } else if (!$newwidth) {
            $m = $image->width / $image->height; // Multiplier to work out $newwidth.
            $newwidth = $newheight * $m;
        }

        $t = null;
        switch ($image->type) {
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $im = imagecreatefromgif($originalfile);
                } else {
                    debugging('GIF not supported on this server');
                    return false;
                }
                // Guess transparent colour from GIF.
                $transparent = imagecolortransparent($im);
                if ($transparent != -1) {
                    $t = imagecolorsforindex($im, $transparent);
                }
                break;
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $im = imagecreatefromjpeg($originalfile);
                } else {
                    debugging('JPEG not supported on this server');
                    return false;
                }
                // If the user uploads a jpeg them we should process as a jpeg if possible.
                if (function_exists('imagejpeg')) {
                    $imagefnc = 'imagejpeg';
                    $imageext = '.jpg';
                    $filters = null; // Not used.
                    $quality = 90;
                } else if (function_exists('imagepng')) {
                    $imagefnc = 'imagepng';
                    $imageext = '.png';
                    $filters = PNG_NO_FILTER;
                    $quality = 1;
                } else {
                    debugging('Jpeg and png not supported on this server, please fix server configuration');
                    return false;
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $im = imagecreatefrompng($originalfile);
                } else {
                    debugging('PNG not supported on this server');
                    return false;
                }
                break;
            default:
                return false;
        }

        // The default for all images other than jpegs is to try imagepng first.
        if (empty($imagefnc)) {
            if (function_exists('imagepng')) {
                $imagefnc = 'imagepng';
                $imageext = '.png';
                $filters = PNG_NO_FILTER;
                $quality = 1;
            } else if (function_exists('imagejpeg')) {
                $imagefnc = 'imagejpeg';
                $imageext = '.jpg';
                $filters = null; // Not used.
                $quality = 90;
            } else {
                debugging('Jpeg and png not supported on this server, please fix server configuration');
                return false;
            }
        }

        if (function_exists('imagecreatetruecolor')) {
            $newimage = imagecreatetruecolor($newwidth, $newheight);
            if ($image->type != IMAGETYPE_JPEG and $imagefnc === 'imagepng') {
                if ($t) {
                    // Transparent GIF hacking...
                    $transparentcolour = imagecolorallocate($newimage , $t['red'] , $t['green'] , $t['blue']);
                    imagecolortransparent($newimage , $transparentcolour);
                }

                imagealphablending($newimage, false);
                $color = imagecolorallocatealpha($newimage, 0, 0,  0, 127);
                imagefill($newimage, 0, 0,  $color);
                imagesavealpha($newimage, true);

            }
        } else {
            $newimage = imagecreate($newwidth, $newheight);
        }

        imagecopybicubic($newimage, $im, 0, 0, 0, 0, $newwidth, $newheight, $image->width, $image->height);

        $fs = get_file_storage();

        $newimageparams = array('contextid'=>$context->id, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>'/');

        ob_start();
        if (!$imagefnc($newimage, NULL, $quality, $filters)) {
            return false;
        }
        $data = ob_get_clean();
        imagedestroy($newimage);
        $newimageparams['filename'] = $resizefilename;

        $file1 = $fs->create_file_from_string($newimageparams, $data);


        return $file1->get_id();
    }

    function filter($text, array $options = array()) {

        $fs = get_file_storage();
        $fb = get_file_browser();

        $matches = [];
        //preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $text, $matches);
        preg_match_all('|<img.*?>|', $text, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $imghtml) {
                $hrefmatches = [];
                preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $imghtml, $hrefmatches);
                foreach ($hrefmatches[1] as $href) {

                    preg_match('/(?<=pluginfile.php).*$/', $href, $filematches);
                    if (!empty($filematches[0])){
                        $relativepath = $filematches[0];

                        // extract relative path components
                        $args = explode('/', ltrim($relativepath, '/'));

                        if (count($args) < 3) { // always at least context, component and filearea
                            //print_error('invalidarguments');
                            continue;
                        }

                        $contextid = (int)array_shift($args);
                        $component = clean_param(array_shift($args), PARAM_COMPONENT);
                        $filearea  = clean_param(array_shift($args), PARAM_AREA);
                        $filename = clean_param(array_shift($args), PARAM_FILE);
                        $fi = pathinfo($filename);

                        list($context, $course, $cm) = get_context_info_array($contextid);

                        $finfo = $fb->get_file_info($context, $component, $filearea, null, null, $filename);
                        if (!$finfo) {
                            continue;
                        }
                        $fparams = $finfo->get_params();
                        $file = $fs->get_file($contextid, $component, $filearea, $fparams['itemid'], $fparams['filepath'], $filename);

                        $timemodified = $finfo->get_timemodified();

                        // Do we have resized versions for this filename and timestamp?
                        $filesizes = ['small' => 320, 'medium' => 768, 'large' => 1024];
                        $urlsbysize = [];
                        $tempfile = false;
                        foreach ($filesizes as $key => $size) {
                            $resizedname = $fi['filename'].'_'.$key.'.'.$fi['extension'];
                            $finfo = $fb->get_file_info($context, $component, $filearea, null, null, $resizedname);
                            if (!$finfo || $finfo->get_timemodified() < $timemodified) {
                                if (!$tempfile) {
                                    // We have to copy the current image to a temporary file because moodle doesn't
                                    // let you get the actual on disk path for a file - $file->get_content_file_location
                                    // is a protected method.
                                    $tempfile = $file->copy_content_to_temp();
                                }
                                $nimage = $this->copy_resize_image($context, $component, $filearea, $file->get_itemid(), $tempfile, $resizedname, $size);
                                $urlsbysize[$key] = $nimage->get_url();
                            } else {
                                $urlsbysize[$key] = $finfo->get_url();
                            }
                        }

                        $pattern = '/src="(.*?)"/';

                        // Build srcset.
                        $srcset = '';
                        $firsturl = '';
                        foreach ($urlsbysize as $key => $url) {
                            $firsturl = $firsturl == '' ? $url : $firsturl;
                            $srcset .= $srcset == '' ? '' : ', ';
                            $srcset .= $url.' ['.$key.'width]w';
                        }
                        $srcset .= ', '.$href.' [extralargewidth]w';


                        $newimghtml = preg_replace($pattern, 'src="'.$firsturl.'" srcset="'.$srcset.'"', $imghtml);

                        // replace old image html with srcset imghtml
                        $text = str_ireplace($imghtml, $newimghtml, $text);

                    }
                }
            }
        }

        return $text;
    }
}

