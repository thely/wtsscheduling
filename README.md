# Installation instructions

###Install XAMPP.
XAMPP gives you a localhost server, which you can use to execute PHP code and interact with a MySQL database. If you're using XAMPP, make sure you can start the Apache server and the MySQL server without errors.

###Clone the git repository.
On Windows, go to (by default) `C:\xampp\htdocs` and make a new folder, probably called `wordpress`. In this directory, type the following:
	git init
	git clone https://github.com/thely/wtsscheduling.git

Type `ls` to make sure a new directory called `wtsscheduling` was created, and `cd` into it.

###Initialize your Wordpress install.
Using some [reliable Wikihow instructions](http://www.wikihow.com/Install-Wordpress-on-XAMPP), get the Wordpress install from the Git repo up and running. Many of these steps are already complete, so you only need to worry about:

-Steps 3-5, which initialize the MySQL database
-Steps 9-13, which give initial settings to the Wordpress site.
-NOTE: The link the how-to provides in step 9 is incorrect, as we have one extra layer of folders due to Git. Your link will be http://localhost/(foldername)/wotsscheduling/wp-admin/install.php, where `foldername` will be whatever the new folder you made was in the *Clone the git repo* step.
