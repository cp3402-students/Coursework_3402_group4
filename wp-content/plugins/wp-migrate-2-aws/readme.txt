=== WP on AWS ===
Author: Seahorse
Contributors: wpseahorse, echomedia
Tags: migration, aws, migrate, manage, clone
Requires at least: 4.8
Tested up to: 6.0
Requires PHP: 5.6
Stable tag: trunk

Easily Migrate, Clone or Transfer sites to AWS from within your WordPress interface without any AWS knowledge! View this site on AWS Now for FREE!

== Description ==

Migrate, Clone or Transfer sites to AWS in 4 simple steps. Once migrated this software allows you to easily manage AWS Hosting via your WordPress interface. View your site on AWS today absolutely **FREE!** No AWS Cloud expertise required.

**Get Free Licence Key** Simply [Click Here](https://www.seahorse-data.com/checkout?edd_action=add_to_cart&download_id=8272) to get a licence key to use the plugin today. Clone any site now to see your site in AWS for Free! No license fees or AWS usage charges will apply. Trial instances stay active for 36 hours after launch.

**AWS Trial User Guide** [Link to AWS Labs](https://aws.amazon.com/getting-started/hands-on/migrating-a-wp-website/)

**AWS User Guide** [Link to Self Managed User Guide](https://www.seahorse-data.com/migrating-and-managing-wordpress-with-amazon-lightsail-self-manage/)

### YOUR WORDPRESS AWS MANAGEMENT ASSISTANT

= What does it do? =
[WP on AWS](https://www.seahorse-data.com/) provides an AWS data transfer & architecture build solution through a simple to navigate interface **and** a control panel to easily manage the site's AWS infrastructure once transferred.  **No AWS Cloud Knowledge Required**

= Who is this plugin for? =
This plugin is primarily aimed at WordPress Webmasters (Site Owners), Developers & Agencies

#### ROADMAP:
* v.1.9.1 Release - Jun 05, 2020 [WCEU](https://2020.europe.wordcamp.org/)

* v.2.3.0 v.2 Self-Managed Solution Release: Nov 30, 2020 [Link to Self Managed Tutorial](https://www.seahorse-data.com/migrating-and-managing-wordpress-with-amazon-lightsail-self-manage/)

* v.3.2.* v.3 Self-Managed Solution Release: Jun 02, 2022 [WCEU](https://europe.wordcamp.org/2022/)

== Installation ==
To install this plugin:

1. Upload the entire 'wp-migrate-2-aws' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 4.1.0 =
* feature - #56 updated functionality to identify & isolate large database tables and uploads

= 4.0.0 =
* feature - #50 added new functionality to allow for instance size selection at launch
* feature - #54 added new functionality to support 'clone', 'staging', and 'upgrade'. Including new user interface, menus and navigation pages

= 3.2.1 =
* bugfix - #51 error handling on licence validation check

= 3.2.0 =
* fix - #39 updated error handling on remote API calls
* feature - #43 include global constants for URLs
* feature - #44 added "restart" button and relocated existing "reset all" button
* feature - #45 include option to run process over http

= 3.1.0 =
* feature - #32 updated launch-success view layout & contents
* bugfix - #35 switch-off multi-site support
* feature - #36 updated configuration for remote manage API
* fix - #37 removed check for amazon polly plugin conflict
* testing - #38 tested to WordPress version 5.9
* fix - #41 updates to text, image, and external links on launch screen

= 3.0.20 =
* Added SNS Alert if Remote Connection Fails

= 3.0.19 =
* updated configuration for remote migration API
* updated configuration for remote manage API

= 3.0.18 =
* fixed error message display for the buckets not found

= 3.0.17 =
* added instance launch time & removed ip from final screen

= 3.0.16 =
* error output bug fix

= 3.0.15 =
* include tables dir by default

= 3.0.14 =
* No functional changes

= 3.0.13 =
* No functional changes

= 3.0.12 =
* bug fix - add empty required directory

= 3.0.11 =
* notice for self managed user about instance size
* License version displayed in welcomePanel

= 3.0.10 =
* major release: moving code to remote API

= 2.5.21 =
* ls naming conventions bug fix

= 2.5.19 =
* Add bucket name sanitization (trial users)

= 2.5.15 =
* Conflict alert when 'AWS for WordPress' 4.3.1 enabled (temporary)

= 2.5.10 =
* further automation of the trial management process

= 2.4.31 =
* if get_object_vars() disabled alternative

= 2.4.12 =
* bug fix at clone (post release)

= 2.4.11 =
* wp 5.7 test minor release

= 2.4.10 =
* S3 Management inc. bucket exists check

= 2.3.33 =
* Update to text related to self-managed process

= 2.3.10 =
* closing region selection on trial

= 2.3.0 =
* major release: self managed model

= 2.0.51 =
* after launch process edit

= 2.0.45 =
* run DB process as a background process

= 2.0.31 =
* menu display debug (permissions - isolated case)

= 2.0.1 =
* release from beta

= 1.9.94 =
* interface styling upgrade (part 2)

= 1.9.81 =
* plugin interface styling upgrade

= 1.9.72 =
* minor bug fix - incompatibility with plugins

= 1.9.71 =
* minor bug fix (Key Pair creation)

= 1.9.7 =
* introducing remote logging
* wp-5.5 compatibility testing

= 1.9.6 =
* initial license management

= 1.9.5 =
* activating large file isolation

= 1.9.4 =
* large file bypass and vendor conflict fix applied

= 1.9.3 =
* patch applied to guest login issue

= 1.9.1 =
* major release: upgraded migration process & management dashboard (beta)

= 1.4.3 =
* minor edits and introduction of SNS logging

= 1.4.1 =
* promoting admin migration process as default

= 1.4.0 =
* manual extract method added (admin)

= 1.3.1 =
* expansion of managed migration service

= 1.2.2 =
* dynamic process display

= 1.2.1 =
* improvement in the handling of process failures

= 1.2.0 =
* Update to transfer process to allow for interruptions to PHP

== Upgrade Notice ==

= interruptions to PHP =

= adopting admin migration process as default =

== Frequently Asked Questions ==

= Do I need my own AWS Account? =

You do not need an AWS account to run a trial clone. A self-manage licence however requires an AWS account as this is where the instance will be built. Seahorse offers an account creation service for users if required.

= Where are Trial clones created? =

Trial instances are created in our Seahorse / AWS environment.

= Why is there no option to select another Region? =

Trials are restricted to the eu-west-1 (Ireland) region only. Users have the option to select any of the Lightsail regions however when using a production licence

= Does your software support multisite networks? =

No, the software does not currently allow migration or management of WordPress multisite. Multisite support is part of our development roadmap however and will be delivered later in 2021.

= For how long are trial clones active? =

Trial instances are active for 36 hours after launch.

= Can I log into the Trial clone instance and make changes? =

No, the trial instance is for front-end review only and access to the WordPress admin of the site is restricted.

= What plan level is the Trial instance running on? =

Trial instances are launched on the $10 Lightsail plan. More information on plan types can be found here: https://aws.amazon.com/lightsail/pricing/. On production licenses the initial migration is also launched on the $10 plan but users are free from there to scale up/down as they wish