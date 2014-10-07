# Installation instructions

###Install XAMPP
XAMPP gives you a localhost server, which you can use to execute PHP code and interact with a MySQL database. If you're using XAMPP, make sure you can start the Apache server and the MySQL server without errors.

###Clone the git repository
On Windows, go to (by default) `C:\xampp\htdocs` and make a new folder, probably called `wordpress`. In this directory, type the following:

	git init
	git clone https://github.com/thely/wtsscheduling.git

Type `ls` to make sure a new directory called `wtsscheduling` was created, and `cd` into it.

###Initialize your Wordpress install
Using some [reliable Wikihow instructions](http://www.wikihow.com/Install-Wordpress-on-XAMPP), get the Wordpress install from the Git repo up and running. Many of these steps are already complete, so you only need to worry about:

-Steps 3-5, which initialize the MySQL database
-Steps 9-13, which give initial settings to the Wordpress site.
-NOTE: The link the how-to provides in step 9 is incorrect, as we have one extra layer of folders due to Git. Your link will be http://localhost/(foldername)/wotsscheduling/wp-admin/install.php, where `foldername` will be whatever the new folder you made was in the *Clone the git repo* step.

###Making changes, getting updated code
`git pull`: updates your version of the code with the most recently pushed revisions. If you are about to push changes and haven't pulled the most recent revision, git will force you to do a git pull first.

To push new code to the repo:
`git add [name of your file(s)]`: do this for all the files you want to submit
`git commit -m "message"`: combines your added files into a single commit, with a message to explain your recent changes.
`git push`: pushes every added file in your commit to the repo

For more git learning, try [Git Immersion](http://gitimmersion.com/).
