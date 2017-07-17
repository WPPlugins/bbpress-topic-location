=== bbPress Topic Location ===
Contributors: G.Breant
Donate link: http://dev.pellicule.org/?page_id=9515
Tags: bbpress,google maps,geolocation,,geo-location
Requires at least: 3.3
Tested up to: 3.3
Stable tag: trunk

This plugin adds the ability to geo-locate a topic in bbPress.

== Description ==

This plugin adds the ability to geo-locate a topic in bbPress.  Originally developped for a classified ads forum (allowing ads to be geo-located).

= Features =

* Works both for frontend & backend.
* Powered by the w3c geolocation; which means that the plugin can guess your current location if your browser has this feature.
* Saves latitude & longitude separately in posts metas ('_bbp_topic_geo_long' and '_bbp_topic_geo_lat'), allowing to search potentially posts by location.  Other informations (location requested, address returned) are saved in a third post meta ('_bbp_topic_geo_info').

== Installation ==

* Copy this plugin directory to `wp-content/plugins/`.
* Activate plugin.


== Frequently Asked Questions ==

== Screenshots ==

1. Topic edition (frontend)
2. Topic in topics list
3. Topic edition (backend)

== Changelog ==
= 1.0.1 =
* Screenshots, readme update, css fixes.
= 1.0.0 =
* Initial launch