/*
 * Block editor: the "To confirm" text format (inc/legal.php). Marks wording that counsel still has to confirm;
 * on the site it shows highlighted (.lg-confirm in pages.css).
 */
(function (wp) {
  'use strict';
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  var NAME = 'labora/confirm';

  wp.richText.registerFormatType(NAME, {
    title: __('To confirm', 'labora'),
    tagName: 'span',
    className: 'lg-confirm',
    attributes: { title: 'title' },
    edit: function (props) {
      return el(wp.blockEditor.RichTextToolbarButton, {
        icon: 'flag',
        title: __('To confirm', 'labora'),
        isActive: props.isActive,
        onClick: function () {
          props.onChange(wp.richText.toggleFormat(props.value, {
            type: NAME,
            attributes: { title: 'To be confirmed by counsel' }
          }));
        }
      });
    }
  });
})(window.wp);
