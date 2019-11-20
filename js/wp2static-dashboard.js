document.addEventListener("DOMContentLoaded", () => {
  console.log('loaded dashboard scripts')

  const sendWP2StaticAJAX = (ajaxAction, successCallback, failCallback) => {
    console.log('sending AJAX from dashboard')

    const formData = new FormData()

    formData.set("ajax_action", ajaxAction)
    formData.set("action", "wp_static_html_output_ajax")
    formData.set("nonce", document.getElementById('wp2static_dashboard_nonce').value)

    const searchParams = new URLSearchParams(
      formData,
    )

    // sort searchParams for easier debugging
    searchParams.sort()

    const data = searchParams.toString()

    const request = new XMLHttpRequest()
    request.open("POST", ajaxurl, true)
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
    request.onload = successCallback
    request.onerror = failCallback
    request.send(data)
  }

  document.addEventListener('click', (event) => {
    if (event.target.matches('#wp2static_dashboard_deploy')) {
      console.log('trigger server-side deploy')


      sendWP2StaticAJAX('dothedashboardthing', () => {}, ()=> {})
    }
  });
    
});
