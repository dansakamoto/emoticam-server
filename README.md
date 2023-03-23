# emoticam-server

[Emoticam](//www.emoticam.net) is a program that ran (consensually) in the background on a bunch of personal computers, from 2013-2018.
Anytime a user typed something to imply they were emoting in real life, it took a photo of their face and uploaded it to the project page (and formerly to Twitter).

This is the server-side software.
* docking.php handles receiving images uploaded by the desktop app
* index.php handles adding received images to the database + displaying all images
* settings.php stores database credentials and unique user codes for known participants
* receiver/poster.php handles sending new posts to Twitter (now defunct).

[The client app can be found here](https://github.com/dansakamoto/emoticam-app).

Note: there was an issue with certain Mac models purchased after the project began which caused some of the images to be underexposed. This is the reason why there are so many solid black images in the later years.
