# Installation instructions

### Install XAMPP
XAMPP gives you a localhost server, which you can use to execute PHP code and interact with a MySQL database. If you're using XAMPP, make sure you can start the Apache server and the MySQL server without errors.

### Clone the git repository
In the below, we're going to assume that paths are relative to the xampp installation directory (in Windows, by default, this is `C:\xampp`)

Open a command prompt/terminal and navigate to `htdocs` and type the following:

	git clone https://github.com/thely/wtsscheduling.git

You should then have a folder called `wtsscheduling`, confirm with `dir`/`ls`. Rename this folder to `wordpress` (via windows explorer/`move wtsscheduling wordpress`/`mv wtsscheduling`).

###Initialize your Wordpress install
Using some [reliable Wikihow instructions](http://www.wikihow.com/Install-Wordpress-on-XAMPP), get the Wordpress install from the Git repo up and running. Do the instructions with the following changes:

* Since we already have a `wordpress` folder after cloning the repo, skip step 1 and for step 2 just ensure that Apache and MySql are enabled in the XAMPP control panel.
* Follow steps 3 - 5.
* From the root directory of the repo, run `git update-index --assume-unchanged wp-config.php`. This is because we don't want your specific changes to this file tracked and pushed back to the repo, but also don't want git to remind us that it has untracked changes every time you are looking to make a commit.
* For step 6, instead of editing `wp-config-sample.php` edit `wp-config.php`.
* For step 7 everything should be ok so you can skip this step, but if you changed the default username/password for MySQL then you can set those here.
* Skip everything else. We need to load up a copy of the database into your local database. Open up `localhost/phpmyadmin`. On the far left you should see a list of databases. Select the `wordpress` database we created earlier.
* Click on the `Import` tab, then select `Choose File`. If you did everything the same as above, then you should be able to navigate to and select the file `htdocs/wordpress/db-backup.sql`. This is a snapshot of the database as of 2014-11-13. If a more recent snapshot is needed (or just in general so it is easier to propogate changes from the main site back to local machines as needed), read the section below on backing up and exporting the site.
* Scroll down to the bottom of the page and click `Go`. After a few seconds you should get a confirmation message saying something like `Import Complete! ...`. Continue below.
* You should now see a list of tables on the left sidebar. Select the table `wp-options`. You should now see a table with the table rows populated into it. One of the first options should be `siteurl` double-click on its value and change it to (by default) `http://localhost/wordpress`.
* Go to the next page and do the same thing for the option named `home`.
* You should now see a local copy of the site available at `http://localhost/wordpress`!

###Backing up main site
If you were following the directions above you may wonder how we got that sql file and what we can do to get a new one if needed. Using a plugin "WP BackItUp" makes the whole thing easy.

If a new backup needs to be generated, just login to the administrative panel of the main site and in the left sidebar select `WP BackItUp`. On this page, click the `Backup` button and wait a few minutes. During this time it is normal for the admin pages, website, everything to be unresponsive, so just sit tight until it is complete (and also don't do this very often). Once it is done you should see a new entry under `Available Backups` on that page. Click the `Download` link and the zip file download should start. Once that file is downloaded you can unzip it and inside you will find a copy of the `wp-content` directory including a file called `db-backup.sql`. This file can be imported locally to update your version of the database, but beware that you will lose all changes to the database that you've made locally (this can include changes to forms, pages, and the like).

###Making changes, getting updated code
- `git pull`: updates your version of the code with the most recently pushed revisions. If you are about to push changes and haven't pulled the most recent revision, git will force you to do a git pull first.

To push new code to the repo:
- `git add [name of your file(s)]`: do this for all the files you want to submit
- `git commit -m "message"`: combines your added files into a single commit, with a message to explain your recent changes.
- `git push`: pushes every added file in your commit to the repo

For more git learning, try [Git Immersion](http://gitimmersion.com/).
