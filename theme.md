# Theme development Overview
While you as developer of the site, you are able to change various features and functionalities of this website via WordPress;
but for some crucial parts such as how the website looks, how will user navigates the site,
plus many other important factors are only able to be applied with altering of codes.
To headsup, modification on theme can have huge impact over the look of your site,
so please watch out for all the changes you apply to the theme.

(Regards how to apply those changes, you can check out the [deployment.md](/deployment.md) for more details).

For information regards the theme, and how it was created, and the major points to look for when modifying it can be found below.

## Theme Origin
Our theme originated from the default theme twenty-twenty-two, the default theme contains some basic functionality, then we use wordpress theme editor combined with custom html component to create basic theme header, footer and layout. Since the theme editor has many restrictions compared to coding, we then modify the theme using css and html to overcome these restrictions so it achieves similar effects while content is still updatable in wordpress.

## Theme Folder Overview
Look inside the theme folder, we have mutiple files and folder, each one of control some aspects of how the theme is display. These files are crucial to change and update the theme. Below is a summary and explanation for the most important files and sub-folders : 
- BLC-theme
  -  templates (sub-folder): this folder contain html templates that used to generate the theme of all pages. 
    -  page.html: This is the theme template we used althrough it does't contain the actual footer and navigation bar which is load from the theme parts, it define the general structure and layout of the theme.
  -  parts (sub-folder): this the folder contains all template parts such as header (navigation bar) and footer 
    -  header.html: This is the footer template we define the navigation bar, html class tag is specific so we can define css style later.
    -  footer.html: This is the header template we define the footer, html class tag is specific so we can define css style later.
  - index.php: This is the php file merge the theme with the actual webpages, and generate the actual html, css, javascript codes for individual webpages.
  - style.css: This is Cascading Style Sheets define styles for all theme file, we link and define styles for header, footer and other using specified class tag.

## Theme features

## Design Structure


### Typology 
font type:

font weight:

font size:

### Colours
background colour

text colour

### structure
margin

padding
