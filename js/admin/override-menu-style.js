document.addEventListener("DOMContentLoaded", function(event) { 

  // locate the submenu item by partial match of its href value
  const try1ClickPublish = document.querySelector('a[href*="wp2static-try-1-click-publish"]');

  // add a class, for easier style application
  try1ClickPublish.classList.add('try-1-click-publish-menu-item');

  // set to open the link in new window
  try1ClickPublish.target = "_blank";

  // set a custom external href for the submenu item 
  try1ClickPublish.href = 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_content=sidebar';

});
