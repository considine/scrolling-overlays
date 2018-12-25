// jQuery(document).ready(function($) {
//   jQuery('input#myprefix_media_manager').click(function(e) {
//     e.preventDefault();
//     var image_frame;
//     if (image_frame) {
//       return image_frame.open();
//     }
//     // Define image_frame as wp.media object
//     image_frame = wp.media({
//       title: 'Select Media',
//       multiple: false,
//       library: {
//         type: 'video'
//       }
//     });

//     image_frame.on('close', function() {
//       // On close, get selections and save to the hidden input
//       // plus other AJAX stuff to refresh the image preview

//       if (urls.length === 0) {
//         return;
//       }
//       const data = {
//         action: 'add_review',
//         url: urls[0],
//         type: 'VIDEO'
//       };
//       const postData = JSON.parse(JSON.stringify(data));

//       jQuery.post(ajaxurl, postData, function(resp) {
//         const response = JSON.parse(resp);
//         if (response.success && response.shortcode) {
//           jQuery('#shortcodeResp').text(response.shortcode);
//         }
//         // Generate shortcode,
//         // want ID of item
//         // and type
//       });
//       // Refresh_Image(ids);
//     });

//     image_frame.open();
//   });
// });

// // Ajax request to refresh the image preview
// function Refresh_Image(the_id) {
//   var data = {
//     action: 'myprefix_get_image',
//     id: the_id
//   };

//   jQuery.get(ajaxurl, data, function(response) {
//     if (response.success === true) {
//       jQuery('#myprefix-preview-image').replaceWith(response.data.image);
//     }
//   });
// }

class MediaFetcher {
  constructor(onMediaCb, type = 'video') {
    this.onMediaCb = onMediaCb;
    this.image_frame = wp.media({
      title: 'Select Media',
      multiple: false,
      library: {
        type
      }
    });

    this.image_frame.on('close', this.onFrameClose.bind(this));
    this.image_frame.on('open', this.onFrameOpen.bind(this));
  }

  open() {
    this.image_frame.open();
  }

  onFrameOpen() {}

  onFrameClose() {
    var selection = this.image_frame.state().get('selection');
    const urls = [];
    var my_index = 0;
    selection.each(function(attachment) {
      urls[my_index] = attachment.attributes.url;
      my_index++;
    });

    if (this.onMediaCb) {
      this.onMediaCb(urls);
    }
  }
}

jQuery(document).ready(function($) {
  const videoFetcher = new MediaFetcher(function(urls) {
    const data = {
      action: 'add_review',
      url: urls[0],
      type: 'VIDEO'
    };
    jQuery.post(ajaxurl, data, function(resp) {
      const response = JSON.parse(resp);
      if (response.success && response.shortcode) {
        jQuery('#shortcodeResp').text(response.shortcode);
      }
    });
  }, 'video');

  const imageFetcher = new MediaFetcher(function(urls) {
    const data = {
      action: 'add_review',
      url: urls[0],
      type: 'IMAGE'
    };
    jQuery.post(ajaxurl, data, function(resp) {
      const response = JSON.parse(resp);
      if (response.success && response.shortcode) {
        jQuery('#shortcodeResp').text(response.shortcode);
      }
    });
  }, 'image');

  jQuery('input#image_media_manager').click(function(e) {
    e.preventDefault();
    imageFetcher.open();
  });
  jQuery('input#video_media_manager').click(function(e) {
    e.preventDefault();
    videoFetcher.open();
  });
});
