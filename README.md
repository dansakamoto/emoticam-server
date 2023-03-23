# emoticam-server

[Emoticam](//www.emoticam.net) is a program running (consensually) in the background of a bunch of personal computers.
Anytime a user types something to imply they’re emoting in real life, it takes a photo of their face and uploads it to the project page (and formerly to Twitter).

This is the server-side software.
* docking.php handles receiving images uploaded by the desktop app
* index.php handles adding received images to the database + displaying all images
* settings.php stores database credentials and unique user codes for known participants
* receiver/poster.php handles sending new posts to Twitter (now defunct).

[The client app can be found here.](https://github.com/dansakamoto/emoticam-app)
