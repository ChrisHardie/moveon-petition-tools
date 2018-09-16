# Tools for MoveOn.org Petitions

This repository holds PHP tools I've built for working with petitions hosted at petitions.moveon.org.

None of these tools are approved or endorsed by the MoveOn folks. They're subject to problems or total breakage resulting from changes in the MoveOn.org APIs.

## Get Signature Names

`Usage: php get-signature-names.php --list_id 12345`

This tool will generate and output a list of signature names from a Petitions.MoveOn.org petition.

To get the List ID, view the HTML source of your Petition URL and look for the `<meta property="list_id" ...>` HTML tag. Copy that number and use it as the value of the `list_id` command line option.

This script uses the petitions.moveon.org API endpoint behind the site's [React app](https://github.com/MoveOnOrg/mop-frontend) to fetch a count of available signatures, and then iterate through the list to retrieve all the signature names. It discards names without at least one space (looking for full names) and does some basic upper/lowercase cleanup. It outputs a string.

## Author

[Chris Hardie](https://chrishardie.com/)

## License

This project is licensed under the terms of the GNU General Public License v3.0. See LICENSE.txt for details.

