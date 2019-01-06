(function() {
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
        urls[my_index] = {
          url: attachment.attributes.url,
          filename: attachment.attributes.filename
        };

        my_index++;
      });

      if (this.onMediaCb) {
        this.onMediaCb(urls);
      }
    }
  }

  function OnOverlayGenerated(shortcode) {
    const div = document.createElement('div');
    const p = document.createElement('p');
    const c = document.createElement('code');
    p.appendChild(
      document.createTextNode('Shortcode generated! Copy this into your post:')
    );
    c.appendChild(document.createTextNode(shortcode));

    div.appendChild(p);
    div.appendChild(c);
    document.getElementById('shortcodeResp').appendChild(div);
  }

  jQuery(document).ready(function($) {
    const videoFetcher = new MediaFetcher(function(urls) {
      const data = {
        action: 'koptional_scrolling_overlays_create_overlay',
        url: urls[0].url,
        name: urls[0].filename,
        type: 'VIDEO'
      };
      jQuery.post(ajaxurl, data, function(resp) {
        const response = JSON.parse(resp);
        if (response.success && response.shortcode) {
          OnOverlayGenerated(response.shortcode);
        }
      });
    }, 'video');

    const imageFetcher = new MediaFetcher(function(urls) {
      const data = {
        action: 'koptional_scrolling_overlays_create_overlay',
        url: urls[0].url,
        name: urls[0].filename,
        type: 'IMAGE'
      };
      jQuery.post(ajaxurl, data, function(resp) {
        const response = JSON.parse(resp);
        if (response.success && response.shortcode) {
          OnOverlayGenerated(response.shortcode);
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

    jQuery('form#youtubeForm').submit(function(e) {
      e.preventDefault();
      const url = $('input#youtube_url').val();
      $('input#youtube_url').val('');
      const data = {
        action: 'koptional_scrolling_overlays_create_youtube_overlay',
        url
      };
      jQuery
        .post(ajaxurl, data, function(resp_raw) {
          const response = JSON.parse(resp_raw);
          if (response.success && response.shortcode) {
            OnOverlayGenerated(response.shortcode);
          }
        })
        .fail(function(response) {
          const err = JSON.parse(response.responseText);
          alert(err.data);
        });
    });

    // Fetch existing
    jQuery.post(
      ajaxurl,
      {
        action: 'koptional_scrolling_overlays_get_overlays'
      },
      function(resp_raw) {
        const resp = JSON.parse(resp_raw);
        const table = document.createElement('table');

        const header = document.createElement('thead');
        const headRow = document.createElement('tr');
        const shortcodeHeader = document.createElement('td');
        const nameHeader = document.createElement('td');
        const typeHeader = document.createElement('td');

        shortcodeHeader.appendChild(document.createTextNode('Shortcode'));
        typeHeader.appendChild(document.createTextNode('Media Type'));
        nameHeader.appendChild(document.createTextNode('File Name'));

        headRow.appendChild(shortcodeHeader);
        headRow.appendChild(typeHeader);
        headRow.appendChild(nameHeader);

        header.appendChild(headRow);

        table.appendChild(header);

        _.each(resp.data, item => {
          const row = document.createElement('tr');
          const shortcode = document.createElement('td');
          shortcode.appendChild(document.createTextNode(item.shortcode));
          const fname = document.createElement('td');
          const link = document.createElement('a');
          link.setAttribute('href', item.url);
          link.setAttribute('target', '_blank');
          link.appendChild(document.createTextNode(item.name));
          fname.appendChild(link);

          const ftype = document.createElement('td');
          ftype.appendChild(document.createTextNode(item.type));

          row.appendChild(shortcode);
          row.appendChild(ftype);
          row.appendChild(fname);
          table.appendChild(row);
        });

        document.getElementById('pastOverlays').appendChild(table);
      }
    );
  });
})();
