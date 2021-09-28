---
name: Bug report
about: Create a report to help us improve
title: ''
labels: ''
assignees: ''

---

# Before creating an issue / filing a support request

 - [ ] try to troubleshoot the issue yourself (see [Troubleshooting guide](https://forum.wp2static.com/-33/how-to-troubleshoot-a-failing-export))
 - [ ] prepare as much information as possible to help the developer 
 - [ ] Identify the issue as likely: **Theme** / **Plugin** / **Environment** or **WP2Static bug**

## Determining if it's an issue with Theme, Plugin, Environment or a bug in WP2Static

_Work down this list until you've identified what kind of issue this is_

 - switch to a default WordPress theme

If the issue is gone - mark this as a **Theme** related issue

 - disable all plugins besides WP2Static

If the issue is gone:

 - continue to turn back on each plugin, 1 by 1, until you've isolated the offending plugin, mark this as a **Plugin** related issue

- try cloning your site to another environment, such as [Lokl](https://lokl.dev) (optimised for WP2Static) or use a quick Vultr or Digital Ocean VPS for a few hrs (less than $1 cost)

If the issue is gone, mark this as an **Environmental** issue

If issue persists after all this, it may still be related to specific content you have or is an issue with the WP2Static plugin, itself. Send a complete set of information with your issue.


**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment (please complete the following information):**
 - Hosting OS: [ie, Linux, BSD, mac, Windows] + version number
 - Web server setup (ie,  Docker, Laravel Valet, Local by FlyWheel, DevilBox, etc)
 - Hosting company (ie, local computer, WPEngine, etc)

**Log files (please complete the following information):**

Please ensure no sensitive information in your log files, then attach to your issue. 

 - WP2Static Logs (found on WP dashboard > WP2Static > Logs)
 - Server logs (PHP and webserver error logs)


**Additional context**
Add any other context about the problem here.
