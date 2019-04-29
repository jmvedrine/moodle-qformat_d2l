#d2l XML question import format

This Moodle question import format can import questions exported from the desire2learn LMS as a QTI2 zip archive into Moodle.

It was created by Jean-Michel Vedrine.
##Installation
To install, either download the zip file, unzip it, and place it in the moodle/question/format directory. (You will need to rename the directory from "moodle-qformat_d2l" to just "d2l".)

Alternatively, get the code using git by running the following commands in the top level folder of your Moodle install:

git clone git://github.com/jmvedrine/moodle-qformat_d2l.git question/format/d2l
echo '/question/format/d2l/' >> .git/info/exclude


You must visit Site administration and install it like any other Moodle plugin.

It will then add a new choice when you import questions in the question bank.
##Support
Please report bugs and improvements ideas using this forum thread:
https://moodle.org/mod/forum/discuss.php?d=232041

This work was made possible by comments, ideas and test files provided by Moodle users on the Moodle quiz forum:
https://moodle.org/mod/forum/view.php?id=737

Jean-Michel Védrine

WARNING :
I am now retired and I stopped all Moodle related activities.
This repository is here just for history and this work is not maintained any more.
Feel free to fork it and modify it to suit your needs or improve compatibility with recent Moodle versions.
Additionally you can consider contacting the Moodle team and become the new maintainer of this plugin. Thanks