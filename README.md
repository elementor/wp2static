# WP2Static

A WordPress plugin for static site generation and deployment.

Official homepage/docs: [https://wp2static.com](https://wp2static.com)

__Looking for the old version? It's been renamed and improved as [Static HTML Output](https://github.com/WP2Static/static-html-output-plugin).__

![codequality](https://github.com/wp2static/wp2static/workflows/codequality/badge.svg)

## Open Source over profits

WP2Static is an open source project, maintained by many generous developers over the years, including, but not limited to these [contributors on GitHub](https://github.com/WP2Static/wp2static/graphs/contributors). Source code for this core repository and all addons shall always remain publicly available.

## [Docs](https://wp2static.com)

## [Support Forum](https://staticword.press/c/wordpress-static-site-generators/wp2static/)

## [Contributors](https://wp2static.com/contributors)

If you want to do a contribution to project, please, follow next instructions:

1. Fork project with button in top of WP2static github [home page](https://github.com/WP2Static/wp2static)
2. Clone your project to your development computer (please, change <your-account> by your account name):
<br/>``git clone https://github.com/<your-account>/wp2static.git``
3. Make your new branch from **master** naming with:
    1. If you want add new feature: feature-\<name of your feature>
    2. If you want to fix a bug: bug-\<name of bug>
    <br/>
    ``git checkout -b feature-myfeature``
4. Do your commits
5. Push to your repository<br/>
    ``git push origin feature-myfeature``
6. Then go to your github wp2static site and do a pull request:<br/>
In base repository choose _WP2Static/wp2static_ and choose _development_ branch.
7. After Pull Request is approved you need to sync repositories.
8. In your local development add **upstream** branch:<br/>
``git remote add upstream https://github.com/WP2Static/wp2static``
9. Fetch **upstream**<br/>
``git fetch upstream``
10. Checkout your local branch:
``git checkout master``
11. Merge **upstream** with your local:
``git merge upstream/master``
12. You can now make new branches.

###Working example

####Preparing Repository
Fork project WP2static [home page](https://github.com/WP2Static/wp2static)

``git clone https://github.com/ebavs/wp2static.git #clone repository (please,change ebavs by yours, this is only an example)``

Then add WP2Static remote

``git remote add upstream https://github.com/WP2Static/wp2static #add remote``

####Working and Commiting

``git checkout -b feature-newdocumentation #create new branch to do changes``

``git commit -am "my new commits #send new changes``

``git push origin feature-myfeature #push to your repository``

Then **Pull Request** in WP2Static

####Sync Repository

``git fetch upstream #download commits from wp2static repo``

``git checkout master #change to local master branch``

``git merge upstream/master #merge with wpstatic master branch``


Read about WP2Static's [developers, contributors, supporters](https://wp2static.com/contributors).

