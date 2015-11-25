********************
Noxon iRadio gateway
********************
Push your own content onto Noxon iRadio devices:
RSS feeds, text files and MediaTomb server structures.

This tool makes it possible to push own data into the menu
entries

- Internet Radio
- Podcasts
- My Noxon


===================================
Customizung the directory structure
===================================
The ``var/`` directory contains three directories you can fill with
your own content.

================ ==================
Menu item        ``var/`` Directory
================ ==================
Internet Radio   ``internetradio``
Podcasts         ``podcasts``
My Noxon         ``mynoxon``
================ ==================

You can put folders and files into this directories.

The ``internetradio`` directory is hard-coded to display the contents
of a MediaTomb UPnP server.
You can remove the check in ``index.php#handleRequest()`` if you do not
want this.


File types
==========
Directory
  A directory is browsable by your Noxon radio
``.sh`` file
  Shell script which is shown as directory and which gets executed
  when navigating into it.
  Output is shown as it is for ``.txt`` files.

  I use it to control my house's heating system from the radio.
``.auto.sh``
  Shell script which gets executed when browsing the folder.
  The output is integrated into the directory listing with the same
  rules as for ``.txt`` files.

  You can use this to show the current time within the directory listing.
``.txt`` file
  Text files are rendered as un-actionable lists.

  Empty lines get removed, consecutive spaces get collapsed.
``.url`` file
  Podcast feed URL file.

  Simply contains the URL to the podcast's MP3 RSS feed.

File extensions get removed for display purposes.


Sorting
=======
Files and directory are sorted alphabetically and get listed
in this order.

You can prefix your files and directories with ``[0-9]+_``,
which lets you influence sorting and gets removed in the
listings.

Consider the following files::

    01_temp.auto.sh
    02_warmer.sh
    03_colder.sh

Would render as::

    Temperature: 23°C
    warmer
    colder

(given that ``01_temp.auto.sh`` outputs the temperature string)


=====
Setup
=====

Hosts
=====
The following hosts must point to your server and be handled
by this tool::

    radio567.vtuner.com
    radio5672.vtuner.com
    gatekeeper.my-noxon.net


MediaTomb
=========
To be able to browse a MediaTomb server, copy ``data/config.php.dist`` to
``data/config.php`` and fill it with mediatomb web interface credentials.


=======
License
=======
This application is available under the `AGPL v3`__ or later.

__ http://www.gnu.org/licenses/agpl.html


======
Author
======
Written by `Christian Weiske`__, cweiske@cweiske.de

__ http://cweiske.de/
