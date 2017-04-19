# emoticam-server

Emoticam is a program running (consensually) in the background of a bunch of computers.
Anytime a user types something to imply they’re emoting in real life, it takes a photo of their face and uploads it to the project page and to Twitter.

This is the server-side software.
* docking.php handles receiving images uploaded by the desktop app
* index.php handles adding received images to the database + displaying all images
* receiver/poster.php handles sending new posts to Twitter.
