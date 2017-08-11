=== Fotomoto Album Connect ===
Contributors: Fotomoto
Tags: picasa, fotomoto, ecommerce, sell, image, photo, picture, affiliate, plugin, print-on-demand, products, revenue sharing, store, storefront, card, ecard, print, canvas, download, share, free
License: GPL2
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: 3.2.1
Version: 0.1.0

Photo Album Import/Sync for Posts 

== Description ==

Allows images to be imported from remote image albums sources (e.g. Picasa) and associated with posts as attachments.

== Installation ==

1. Download the plugin file.
2. Unzip it to the /wp-content/plugins/ directory
3. Activate the plugin through the "Plugins" menu in Wordpress

== Screenshots ==

1. **Import Album** - Location of Import Album button on "Add New" post page (same location on post edit page).
2. **Settings Page** - The Fotomoto Album Connect Settings page can be found under the Settings Administration Menu along with the settings page itself.
3. **Import Album Dialog** - After pressing "Import Album" a dialog appears giving several choices of albums to import and display options.
4. **Import in Progress** - Importing an album is in progress on "Add New" post page.
5. **Sync Album Button** - Location of Sync button on a post edit page.
6. **Sync Album Dialog** - Sync album dialog with options.
7. **Sync in Progress** - Syncing of album in progress.

== Usage ==


= Settings = 

### Picasa Account Email

In order to import Picasa albums the email associated with your Picasa Web Album account must be supplied on the Album Connect Settings Page.
  
### Picasa Image Import Resolution
This settings allows you to decide what maximum width resolution images from Picasa should be retrieved with by Fotomoto Album Connect. The original aspect ratio of the image is preserved.

= Importing =

In order to import a remote album, first ensure that the Fotomoto Album Connect Settings page has been filled in with valid information. Then, either open an existing post or go to
the "Add New" post page. From here, in the right hand corner there is a meta box called "Fotomoto Album Connect". Within this box click the button labelled "Import Album". A dialog will open
with options for importing and the select box will fill with available albums to import. 


= Syncing =

To sync a post associated with a remote album:
* Go to the edit page of the post in question
* Find the "Fotomoto Album Connect" meta box in the right hand corner.
* Click on "Sync".
* A dialog will be shown with several options, click on "Sync" within dialog to continue.
* Progress shown in "Fotomoto Album Connect" meta box.


= Import/Sync Options =

<h5>Import Album Description</h5>

Toggle whether album descriptions from the remote source should be imported/synced to post.

<h5>Import Photo Captions</h5>

Toggle whether photo captions from the remote source should be imported/synced to post.


== Change Log ==

= Version 0.1.0 =
* Added support for Picasa Web Albums import/sync from post edit page and "Add New" post page