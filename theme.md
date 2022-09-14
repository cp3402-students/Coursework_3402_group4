# Theme development Overview

While you as developer of the site, you are able to change various features and functionalities of this website via
WordPress;
but for some crucial parts such as how the website looks, how will user navigates the site,
plus many other important factors are only able to be applied with altering of codes.
To headsup, modification on theme can have huge impact over the look of your site,
so please watch out for all the changes you apply to the theme.

(Regards how to apply those changes, you can check out the [deployment.md](/deployment.md) for more details).

For information regards the theme, and how it was created, and the major points to look for when modifying it can be
found below.

## Theme Origin

Our theme originated from the starter theme "WP Rig", we then develop the theme from this, we follow a linkedin learning tutorial which give us some guidances on how to develop our own theme on WP Rig theme.

## Theme Folder Overview

Look inside the theme folder, we have multiple files and folder, each one of control some aspects of how the theme is
display. These files are crucial to change and update the theme. Below is a summary and explanation for the most
important files and sub-folders :

- BaizonnLearningCentretheme
    - templates (sub-folder): this folder contain html templates that used to generate the theme of all pages.
    - page.html: This is the theme template we used although it doesn't contain the actual footer and navigation bar
      which is load from the theme parts, it defines the general structure and layout of the theme.
    - parts (sub-folder): this the folder contains all template parts such as header (navigation bar) and footer
    - header.html: This is the footer template we define the navigation bar, html class tag is specific, so we can define
      css style later.
    - footer.html: This is the header template we define the footer, html class tag is specific, so we can define css
      style later.
    - style.css: This is Cascading Style Sheets define styles for all theme file, we link and define styles for header,
      footer and other using specified class tag.

## Theme Design

Our Design in WordPress inherit our design in [figma](https://www.figma.com/file/1ubJjWaeQUFzoygyQkc2WO/CP3402), using figma save us lots of times, so we implement our design without doubt and concerns. The overall style of the follow a simplism approach, therefore we use a bold header design and footer design to make vistor interest in our website, thus may purchase tutoring services.


### Typology

font type:
We select Open Sans for most of the text since it is a simple and elegant font that will make the website more
interesting and attract more user to eventually subscribe the courses on the Website. Roboto Mono is a perfect capitalized font use for logo.

- header: use Roboto Mono in logo, use Open Sans for others
- body: use Open Sans
- footer use Open Sans

font weight: Normal for normal text, bold for header

font size: seperated for normal text and different titles, 32px for most of normal text, and size for titles vary depend on the use case

letter spacing: 0% for normal text in body and footer. In header, use 40% and 20% for big title and small title in logo,
0% for others.

### Colours

common background colour:
white (white),
light grey (#F6F6F6),

common text colour:
vivid green (#00d084)
black (black)
dark blue (#164570)

### structure


padding:
We apply padding to both left and right, which make the page tidy and have better viewing experience.
left: 5%
right: 5%

responsive header: this make different desktop screen with less pixel on width able to view the webpage

