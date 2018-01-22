# webmap

Asynchronous ReactPHP website URL crawler

Usage: ./webmap.php <url> | tee outfile.txt

You can use provided scripts in /bin to do some operations with outfile.txt, like summarize found extensions,
search for URLs of a given extensions, and sort list by response time

# Setup

You must use composer to fetch dependencies. [https://getcomposer.org/download/](https://getcomposer.org/download/)

`php composer.phar update` or `composer update` in the project root.
