jQuery(document).ready(function($){

  // locate the submenu item by partial match of its href value
  const try1ClickPublish = document.querySelector('a[href*="wp2static-try-1-click-publish"]');

  // add a class, for easier style application
  try1ClickPublish.classList.add('try-1-click-publish-menu-item');

  // set to open the link in new window
  try1ClickPublish.target = "_blank";

  // set a custom external href for the submenu item 
  try1ClickPublish.href = 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_content=sidebar';

  // send admin notice dismissal events to backend
  const wp2staticAdminNotice = document.querySelector('.wp2static-admin-notice');

  if ( wp2staticAdminNotice ) {
    wp2staticAdminNotice.addEventListener('click', function (e) {
        if (e.target.className === 'wp2static-admin-notice-dismiss') {
          // notify backend which admin notice was dismissed by user
          dismissedAdminNotice = e.target.id.replace('wp2static-admin-notice-dismiss-', '')

          adminNoticeNonce =
            document.querySelector('#wp2static-admin-notice-nonce').textContent;

          adminNoticeUserID =
            document.querySelector('#wp2static-admin-notice-user-id').textContent;

          const ajax_data = {
              action: 'wp2static_admin_notice_dismissal',
              security: adminNoticeNonce,
              dismissedNotice: dismissedAdminNotice,
              userID: adminNoticeUserID,
          };

          $.ajax({
              url: ajaxurl,
              type: 'POST',
              data: ajax_data,
              timeout: 0,
              success: function() {
                // hide the admin notice once backend has handled it
                wp2staticAdminNotice.remove();
              },
              error: function() {
                alert('Couldn\'t dismiss WP2Static admin notice. Please try again.');
  }
          });
        }
    });

  }
});
