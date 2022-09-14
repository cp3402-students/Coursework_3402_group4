# Site Development Overview

## Site Overview

To provide a maintainable and updatable site for Baizonn Learning Centre,
this document will try to specify and explain how to change the layout,
how to change content on an existing page, how to add a new event post, how to delete and update the plugins and the
content related to theme.
The creation of a new event post to events page, how to edit content on current pages such as schedule and about us,
and other general site configuration required to effectively maintain and update this site.

## Pages

1. #### Index(Homepage)
   Index is the homepage of our website which is the page that all new visitors will get into their first sight by
   default.
   The current home page contains a WordPress media&Text element that display welcome messages and an image,
   and the huge light-green button below our welcome message is linked to the registration page.
   If you have the needs to change where it points to, feel free to change it via the WordPress editor.

   Below the welcome message,
   there is an intro section that briefly introduce the learning centre from multiple perspective in a self Q&A style.
   Text and image can be replaced within WordPress editor, you can change it to suit your needs.
   For the teaching style section, there is a clickable hyperlink that can redirect user to the about us page, which can
   be changed as well.
   As those 3 CTA buttons, we have them redirect to about-us, registration and schedule page respectively.
   Below that, there is a gallery which presents with slideshow.
   The image is pulled from the NexGen gallery plugin and can be changed from there.
   This index page and some other pages have the slideshow **PLUGIN** that provides some custom features to the site.

2. #### About us
   The about us is page that has multiple media&text block.
   The first part is three parallel cards that emphasis on what makes us stand out.
   Following with details that further explain why do we have such claim.
   By default, **learn more** button will redirect the user to our staff page.

   For the award section, there is a **view this** button that redirect user to the award document page,
   which contains a slideshow gallery that display a selection of images.
   There is also a section below that lists out the academic partners which have been working with us.

3. #### Registration
   For the registration page, there is a signup form that implement via WordPress form block which requires various user
   info.
   All sections that ask for user input can be changed within WordPress editor.

   Below the signup form, a shoutout to site visitor to visit learning centre,
   and a CTA button that ask user to check out the location info by redirecting to location&transportation page.

4. #### Schedule
   For schedule page, all kinds of subjects which provided by the learning centre are listed here in a block which can
   be changed within WordPress editor.

   Right below the subjects listing is a CTA hyperlink to registration page which allow visitor to sign up to our
   leaning centre and proceed to make appointment with us. A promotion ads is also displayed here to lure more visitors
   to join for registration.

   After that, it is a timetable which displays the scheduled timetable of the learning centre to show the current
   visitors.
   Another CTA button that promotes visitor to register to the learning centre is display here. This button is
   redirecting to registration page.

5. #### Events
   For the event page, there is a side by side column block at the top, which shows the editor's choice award and the
   open house event.

   By clicking on the **title** of these two block or the **image** or the **readmore** hyperlink, visitor will be able
   to jump to the editor's choice award page and the open house event page for more details. These hyperlink all can be
   altered via WordPress editor.

   Below that is a section which prompts visitors to book a session from the learning centre by emphasising variety
   of the learning centre's strength. A CTA block is also there to lead user try out the booking system. Right below it,
   there is an appointment booking form which enable visitor to book a session via the website,
   and this feature is provided by the appointment booking form plugin.

6. #### Our Staffs
   This page is the place that have a display of the staff(teacher in this case). There are two media&text block used
   here for displaying the content with image. Both text content and the image can be changed via WordPress editor.

7. #### Locations/Transportation
   For this location and transportation page, when you enter it, the first sight would be seen is the map block that
   shows where is the learning centre. This feature is provided via **WP Go Maps** plugin. The default location of the
   map shows can be changed with the plugin setting.

   Below the map, there are three section display route information to get to the learning centre. There are created
   with column block. Everything can be changed here, from the image to the route description for different
   transportations.

## Posts

* Event
   the event post is provide about contents of the event that upcoming there will opening campus event in the post content that are post in the site.
* Award
   The Award post is the part of showing the content of the award that learnign center got. the page will provid content of the text paragraph to explain on the work of the award, on the other hand there are the part that show on the picture of the award and overwview workflow.
## Plugins

* **Simple Custom CSS and JS**
   the plugin the avaialble to edit and deploy on the javascript and CSS seperatly
* **Big File Uploads:** This enables us to overwrite the default upload size limit for media and files,
  the default is 2M which is quite small for any media file today, by using this plugin we can upload all pictures to
  our WordPress website.
* **Forminator:** By using this plugin we enable user of our website submit form to the website owner,
  and the website owner allow to create forms or update any form on the site. Moreover, the plugin also provide scam
  protection for the form, which will benefit the website owner. For more information regards Forminator plugin, you can
  check here: https://wordpress.org/plugins/forminator/
* **Simply Schedule Appointments:** This plugin enable website owner create any appointment booking for user of the
  website.
* **WP Go Maps (formerly WP Google Maps):** This plugin enable website owner display the physic location of their
  business
  on Google map
* **The Events Calendar:** The Events page's subject, times, and prices are added using the Events Calendar. A few
  events have already been added and may be modified for upcoming events. It's easy to establish an event since you can
  choose from pre-made event details that will be included on the calendar right away. Events that are featured can be
  marked, and all recent events are placed to the sidebar widget's "recent events" area. The ability to add ticket sales
  to events and the option to establish recurring events are both significant premium features. You may find information
  about the Events Calendar here: https://wordpress.org/plugins/the-events-calendar/
* **NextGen Gallery:**  The awards gallery which redirect from about us page is built using NextGEN Gallery, a
  supplementary media manager for the website. Add photographs to galleries inside the plugin area, then albums with
  those galleries. You may make Galleries for particular classes, events, subjects, and other things before combining
  them together into one album. The plugin also allows you to control additional elements like gallery styles and
  thumbnail display. You can get more details on NextGEN and potential premium features
  here: https://wordpress.org/plugins/nextgen-gallery/
* **Header and Footer Scripts:** If you are running a WordPress site then sooner or later you need to insert some kind
  of code to your website. It is most likely a web analytics code like Google Analytics or may be social media script or
  some CSS stylesheet or may be Custom fonts. This plugin will do all the magic. Even if you want to insert those codes
  in a custom post type. All you have to do is adding appropriate html code. Don’t forget to wrap your code with proper
  tags.You can get more details on this plugin here: https://wordpress.org/plugins/header-and-footer-scripts/

## Site development

This site is built using WordPress.

So the site manager should download WordPress CMS from WordPress.org.
