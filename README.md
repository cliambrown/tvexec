# tvexec: play local tv episode files from the browser

This is a little project I made to help keep track of shows I've downloaded and which episode I'm on. It's a bit ridiculous, since it requires using (shudder) Internet Explorer, ActiveX objects, and some manual file management. But it works for me, so I wanted to share it.

## Features

* Scans all files in the user-supplied TV directory on request
* Retrieves show & episode information from TVDB
* No internet connection required (except if you want TVDB data)
* Has simple keyboard navigation and pleasing aesthetics
* Gracefully handles moved/missing files, added episodes, and some basic errors
* Makes you use IE, which feels kinda retro maybe

## Why not use Kodi/Plex/etc?

I really like MPC-HC, and all I wanted was a simple tool to easily open the next episode of a show. Those other programs do way more than I need, and they don't seem to play well with MPC. If there's other software out there that does what `tvexec` does, I'd be glad to hear about it!

## Screenshots

[The interface](https://github.com/cliambrown/tvexec/blob/master/screenshot.png)

[With one show expanded](https://github.com/cliambrown/tvexec/blob/master/screenshot2.png)

## Getting Started

### Requirements

* Windows
* Internet Explorer 11 (possibly 9+, but I've only tested it on 11)
* [Media Player Classic](https://mpc-hc.org/)
* a local dev server (like [xampp](https://www.apachefriends.org/index.html)) with PHP and MySQL support
* a TVDB API key (see "Getting a TVDB API Key" below)

### Setup

1. Create or choose a designated TV directory somewhere on your hard drive. Remove all non-TV files from it, and create one folder in it for every show (using the name of the show \['The' is optional\]). Add the episode files into each one (the organization of sub-directories and files inside each show folder is irrelevant).

   **Note: if you have more than ~10 shows or if any shows have a lot of files, you may want to start with just a part of your TV library and add more files in stages later on.**

   Example:
```
   C:\some\path
     > TV
       > Game of Thrones
         > [some files & folders]
       > Lost
         > [more files & folders]
       > Sopranos
         > [even more files & folders]
``` 

2. For each show, download a banner and save it as [folder_name].jpg in `tvexec\img\banners\`. (I use the "legacy banners" from TVDB.)

3. Create a MySQL database for `tvexec`. Also create a user with global privileges for this database. (Take note of the username and password for the next step.)

4. Open `tvexec\includes\auth.php` in a text editor and add your own info to the four variables at the top of the file. Also add the database name in the db variables, plus the username and password you created in step 3.

5. Open `tvexec` in IE.

6. Click \[Gear Icon\] > Internet Options > Security > Trusted Sites > Sites. Uncheck the "Require server verification..." box, then click Add. (The url for your `tvexec` page should now be listed as a trusted site.) Close this window and agree to any prompts that appear.

**NOTE:** ActiveX is SUPER unsafe. Be *very* careful with the following steps, or better yet, maybe just stop here and use Plex or whatever instead?

7. With "Trusted Sites" still selected, click "Custom level..." and scroll down to ActiveX controls. Enable the various ActiveX script options and disable the prompts.

8. Press 'OK' and accept all prompts until finished. (You may need to clear your browser history & cache and restart the browser in order for this to take hold.)

9. Click the "Scan Directory" button and wait. **Note:** This can take a very very long time. If you get an error, you may need to click the button again, or reload the page and try again.

10. When prompted, select the correct TVDB show links (or enter one manually) and enter any missing episode information.

11. \[Optional\] Bookmark the site or create a desktop shortcut or something. (I have a shortcut that opens IE with the `tvexec` url as a command-line switch.)

### Getting a TVDB API Key

Here are the (current) steps to getting an API key:

1. Create an account [here](https://www.thetvdb.com/register) if you don't already have one
2. Log out
3. Log back in
4. Go to https://www.thetvdb.com/member/api and click "Generate API Key"
5. Donate to them because it's a great site

## Usage

### General Steps

1. Watch an episode by clicking the show.
2. When it's done, navigate to the next episode on the `tvexec` page by pressing the right-arrow key.
3. You can remove a show from the page by simply deleting its episode files (or moving them out of your TV folder).

### Controls

| Mouse Click | Keyboard | Action |
| --- | --- | --- |
| n/a | Tab | Select next show |
| n/a | Shift + Tab | Select previous show |
| Show box | Enter / Spacebar | Play displayed episode |
| ⌄ or ⌃ icon | Down/up arrow key | Toggle episode list & nav controls |
| \|< icon | n/a | Go to first episode |
| < icon | left arrow key | Previous episode |
| shuffle icon | 'r' key | Random episode |
| > icon | right arrow key | Next episode |
| >\| icon | n/a | Mark show as watched |
| outside show box | Esc | Close all show drop-downs |

### Troubleshooting

#### Directory Scan just goes on forever

This can take a few minutes, which on a computer screen can feel like an hour. If it's really frozen, just reload the page and try again.

#### Selected the wrong TVDB show and now all that show's episodes have incorrect names *(Note: try to avoid this)*

1. Move all episodes for that show out of the TV folder.
2. Manually update the show's TVDB id (e.g. using phpMyAdmin) to either zero or the correct TVDB id.
3. Click the "Scan Directory" button and click through until finished.
4. Move the show's files back into the show folder.
5. Do another directory scan.

#### Episode positions are always 0:00

In MPC, go to Options > Player and check the "Remember File position" box. (I'd also recommend changing Advanced > RecentFilesNumber to 40.)

#### Some shows / episodes are not showing up

The scanner finds mkv, mp4, avi, wmv, and mov files. If your episodes are in a different format, you'll need to add it to `tvexec\scripts\scan_show_dir.php` in the part that looks like this:
```
$epPathnames = get_all_files($tvDir.DIRECTORY_SEPARATOR.$show['showName'], ['avi','mkv','mp4','mov','wmv']);
```

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* James Heinrich for [getID3](https://github.com/JamesHeinrich/getID3)
