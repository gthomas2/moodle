YUI.add('moodle-atto_bsgrid-button', function (Y, NAME) {

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

/*
 * @package    atto_bsgrid
 * @copyright  2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_media-button
 */

/**
 * Atto bsgrid tool.
 *
 * @namespace M.atto_bsgrid
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTCLASS = 'atto_bsgrid',

    TEMPLATE = '' +
        '<div id="{{elementid}}_atto_bsgrid" class="{{COMPONENTCLASS}}">Grid selection to go here</div>';

Y.namespace('M.atto_bsgrid').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    /**
     * A reference to the dialogue content.
     *
     * @property _content
     * @type Node
     * @private
     */
    _content: null,

    initializer: function() {

        this.addButton({
            icon: 'e/insert_edit_video',
            callback: this._displayDialogue
        });

    },

    /**
     * TODO
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();
        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: 'TODO - lang string - bootstrap grid',
            focusAfterHide: true
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent())
                .show();
    },

    /**
     * TODO
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile(TEMPLATE);

        this._content = Y.Node.create(template({
            component: COMPONENTCLASS,
            elementid: this.get('host').get('elementid')
        }));

        //this._content.one('.submit').on('click', this._insertGrid, this);

        return this._content;
    },

    /**
     * Insert the grid
     *
     * @method setMedia
     * @param {EventFacade} e
     * @private
     */
    _insertGrid: function(e) {
        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        // TODO
        return;

        var form = e.currentTarget.ancestor('.atto_form'),
            url = form.one(SELECTORS.URLINPUT).get('value'),
            name = form.one(SELECTORS.NAMEINPUT).get('value'),
            host = this.get('host');

        if (url !== '' && name !== '') {
            host.setSelection(this._currentSelection);
            var mediahtml = '<a href="' + Y.Escape.html(url) + '">' + name + '</a>';

            host.insertContentAtFocusPoint(mediahtml);
            this.markUpdated();
        }
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
