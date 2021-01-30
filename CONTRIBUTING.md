## Versioning & branches

`develop` branch is considered unstable with latest code changes (current build status: ![codequality](https://github.com/leonstafford/wp2static/workflows/codequality/badge.svg?branch=develop)). `develop` branch should always have a `-dev` WordPress plugin version, ie `7.1.1-dev`.

`master` branch should always reflect a stable release, such as `7.1.1`, which should have a matching tag.

## Beginner-friendly contributing

Please don't be intimidated to contribute code to this project. I welcome code
 in any way you're comfortable to contribute it (email, forum, diff). If you're
 new to GitHub and this kind of thing, the below guide may help you. 

1. Fork project with button in top of WP2static github [home page](https://github.com/leonstafford/wp2static)
1. Clone your project to your development computer (please, change <your-account> by your account name):  
   `git clone https://github.com/<your-account>/wp2static.git`
1. Fork your new branch from **develop** naming with:
   1. If you want add new feature: `feature-<name of your feature>`
   1. If you want to fix a bug: `bug-<name of bug>`  
      `git checkout -b feature-myfeature`
1. Do your commits
1. Push to your repository  
   `git push origin feature-myfeature`
1. Then go to your https://github.com/<your-account>/wp2static site and create a pull request:  
   In base repository choose _leonstafford/wp2static_ and choose _development_ branch.
1. After Pull Request is approved you need to sync repositories.
1. In your local development add **upstream** branch:  
   `git remote add upstream https://github.com/leonstafford/wp2static`
1. Fetch **upstream**  
   `git fetch upstream`
1. Checkout your local branch:  
   `git checkout develop`
1. Merge **upstream** with your local:  
   `git merge upstream/develop`
1. You can now make new branches.

### Working example

#### Preparing Repository

Fork project WP2static [home page](https://github.com/leonstafford/wp2static)

`git clone https://github.com/ebavs/wp2static.git # clone repository (please,change ebavs by yours, this is only an example)`

Then add WP2Static remote

`git remote add upstream https://github.com/leonstafford/wp2static # add remote`

#### Working and Commiting

`git checkout -b feature-newdocumentation # create new branch to do changes`

`git commit -am "my new commits" # send new changes`

`git push origin feature-myfeature # push to your repository`

Then **Pull Request** in WP2Static

#### Sync Repository

`git fetch upstream # download commits from wp2static repo`

`git checkout develop # change to local develop branch`

`git merge upstream/develop # merge with wpstatic develop branch`

### Publishing a new release

This is currently done by @leonstafford and involves these steps:

 - test code in `develop` branch
 - set a new dev version if needed, ie `7.1.1-dev`
 - merge `develop` branch to `master`
 - adjust `wp2static.php` version to non-dev, ie `7.1.1`
 - update `CHANGELOG.md`
 - create new git tag with matching version
 - push `master` branch and tag to GitHub
 - create new Release in GitHub with same notes as CHANGELOG
 - build zip installer and publish to wp2static.com with MD5 hash

