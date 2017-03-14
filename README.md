Vidyard Embed
=================
Adds support to SilverStripe for embedding Vidyard videos easily into your SilverStripe powered website.

## Maintainer Contact
* Ed Chipman ([UndefinedOffset](https://github.com/UndefinedOffset))

## Requirements
* SilverStripe Framework 3.1+


## Installation
__Composer (recommended):__
```
composer require webbuilders-group/silverstripe-vidyard-embed
```

If you prefer you may also install manually:
* Download the module from here https://github.com/webbuilders-group/silverstripe-vidard-embed/archive/master.zip
* Extract the downloaded archive into your site root so that the destination folder is called kapost-bridge, opening the extracted folder should contain _config.php in the root along with other files/folders
* Run dev/build?flush=all to regenerate the manifest


## Usage
This module provides a new insert media option called "From Vidyard" which functions very similar to the "From Web" however it is specific to Vidyard which does not at this time support oEmbed. The link expected in this field is either the "Sharing Page" or some of the settings pages including the embed select page (for details see [the documentation](docs/en/supported-urls.md)). You will need an API Key from Vidyard as it is used to lookup the information to embed see [here for information](http://support.vidyard.com/articles/Public_Support/Using-the-Vidyard-dashboard-API/) on where to get this. Once you get your api key from Vidyard the user key will suffice. You must add the below to your config before you can add Vidyard videos using this module.

```yml
Vidyard:
    api_key: "YOUR_API_KEY_HERE" #Vidyard API Key
```

If you want you may also add this short code manually to your WYSIWYG by using the below syntax, all arguments are optional.

```
[vidyard,width="600",height="480",class="leftAlone",thumbnail="URL_FOR_THUMBNAIL"]URL_FOR_VIDEO[/vidyard]
```
