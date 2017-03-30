# Easy WordPress VMs with Vagrant and Ansible

This bundle of files automates the process of automatically creating and provisioning local virtual machines with a complete, running instance of WordPress. Such things are useful for local development and testing things like plugins and themes on a "clean" WordPress install.

## Prerequisites

To make use of these files, you'll need to have the following prerequisites installed on your workstation:

* [VirtualBox](https://www.virtualbox.org/)
* [Vagrant](http://www.vagrantup.com/)
* [Ansible](https://www.ansible.com/)

Any Vagrant-based VM needs to start with a Vagrant "box" file -- a canned image of a base system that Vagrant can use as the starting point for further customization. **By default, these scripts use the official Vagrant image distributed by Ubuntu of the 64-bit version of Ubuntu 14.04 LTS, "Trusty Tahr," so you do not need to download or install anything further if you are satisfied with that version.** As of this writing (September 16, 2014) that file is [distributed here](https://vagrantcloud.com/ubuntu/boxes/trusty64).

If you want to build your VM from a different version or distro, check [Vagrant Cloud](https://vagrantcloud.com/discover/featured) or [cloud-images.ubuntu.com/vagrant](http://cloud-images.ubuntu.com) to find the URL of a box you wish to use. If you wish to use a box other than the default, just install the box (if it's not hosted at Vagrant Cloud) and update the Vagrantfile to point to the particular box you wish to use.

Note that these scripts were designed for use with Ubuntu, so they make use of the apt packaging manager and other conventions Debian-derived distributions share (filesystem locations, configuration file structure, etc.). This means they will only work properly out of the box with Debian Linux or Debian-derived distributions such as Ubuntu. If you use Red Hat/CentOS/Fedora, or another distribution that uses a different package manager, consult the Ansible documentation for instructions on how to modify the setup.yml file to change the apt commands to those for your particular package manager and modify file locations so they map to the appropriate places for your distro.

## What It Does

Together with the prerequisites listed above, the scripts contained herein will let you create a new VM with a simple `vagrant up` that:

* Is configured with a static IP address on your local LAN (default 192.168.50.50) so you don't need to constantly be looking up new DHCP-assigned addresses each time it restarts
* Has a complete MySQL 5 setup (client and server) installed
* Has a complete Apache 2 setup installed (with mod_rewrite)
* Has a complete PHP5 setup (both mod_php and CLI versions) installed, with the following modules:
    * php5-curl
    * php5-gd
    * php5-imagick
    * php5-mysql
    * php5-sqlite
    * php5-xcache
    * php5-xmlrpc
    * php5-xdebug (ready for remote debugging on port 10000; use the IDE key "vagrant")
* Installs the latest version of the WordPress software in /vagrant, so you can work with local files via your favorite editor/IDE; sets up symlink to it at /var/www/wordpress`so Apache can find it
* Has a MySQL database (name: "wordpress") and database user (name: "user_wp"; password: "wordpress") for WordPress to make use of
* Has a configuration file in `/root/.my.cnf` to allow the root user to log into MySQL as root without needing to enter a username or password
* Has an Apache virtual host configured and enabled to serve WordPress

So, once the box is provisioned, all you need to do is go to 192.168.50.50 in your browser to begin the famous WordPress 5-minute install.

## Getting Started

To begin, create an empty directory and clone the files in this repository into it.

Then just run the command `vagrant up` and your VM should bootstrap itself into existence, ready to work with. 

[More information on working with Vagrant VMs, including how to shell into a running VM and shut it down safely, is available in the Vagrant documentation.](http://docs.vagrantup.com/v2/getting-started/index.html)

## Customization/Configuration

**If you use the default box, no configuration should be required to get up and running.** However, in the file named `Vagrantfile`, you *may* wish to change the following:

* If you want to use an IP address other than 192.168.50.50, replace that address with the one you wish to use in two places:
    * `Vagrantfile`, on line 9
    * `vagrant-inventory`, on line 2

Finally, it's not necessary in most common use scenarios, but if for some reason you wish to change the configuration of the Apache virtual host or the MySQL configuration for the root user, you can find the templates used to generate those configuration files in the `templates` subdirectory.

If you wish to modify or extend the basic logic that provisions the system -- add new packages, say -- all that logic is in the file `setup.yml`. This file is an Ansible "playbook," so you can make use of any of Ansible's modules or features there. For more information on how to work with Ansible playbooks, refer to their ["Intro to Playbooks"](http://docs.ansible.com/ansible/playbooks_intro.html) document. [A complete reference of all modules available within an Ansible playbook](http://docs.ansible.com/ansible/modules_by_category.html) is also available.

## License

These files are copyright 2014, Jason Lefkowitz.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
