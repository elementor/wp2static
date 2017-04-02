require 'selenium-webdriver'
require 'rspec/expectations'
include RSpec::Matchers

def setup
    caps = Selenium::WebDriver::Remote::Capabilities.send("chrome")
    @driver = Selenium::WebDriver.for(:remote, url: "http://0.0.0.0:4444/wd/hub", desired_capabilities: caps)
    @driver.manage.window.size = Selenium::WebDriver::Dimension.new(1920, 1080)
end

def teardown
    @driver.quit
end

def run
    setup
    yield
    teardown
end

run do
    container_ip = ARGV[0]

    puts "Running tests against container IP: #{container_ip}"

    # Open the main page and check for the title
    site_url = "http://#{container_ip}"
    @driver.get site_url + '/'
    #@driver.save_screenshot(File.join(Dir.pwd, "selenium-docker-main-page.png"))
    expect(@driver.title).to eql 'wp plugindev – Just another WordPress site'

    puts 'Title test OK'

    @driver.get site_url + '/wp-login.php'
    expect(@driver.title).to eql 'wp plugindev ‹ Log In'

    #@driver.save_screenshot(File.join(Dir.pwd, "selenium-docker-login-page.png"))

    #id user_login
    @driver.find_element(name: 'log').send_keys 'admin'
    @driver.find_element(name: 'pwd').send_keys 'admin'
    @driver.find_element(name: 'wp-submit').submit

    expect(@driver.title).to eql 'Dashboard ‹ wp plugindev — WordPress'

    puts 'Login test OK'

    @driver.get site_url + '/wp-admin/tools.php?page=wp-static-html-output-options'

    expect(@driver.title).to eql 'WP Static HTML Output ‹ wp plugindev — WordPress'

    # setup export and run
    @driver.find_element(name: 'baseUrl').send_keys 'http://google.com'
    @driver.find_element(class: 'saveSettingsButton').click

    # get list of files from export folder (should be only one exported folder)
    #puts Dir["/path/to/search/**/*.rb"]

    # check contents of index.html file

    ## Generate a screenshot of the checkbox page
    #@driver.save_screenshot(File.join(Dir.pwd, "selenium-docker-plugin-settings.png"))
end
