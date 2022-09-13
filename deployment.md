# Deployment Overview

## project management

**Github**:
**Discord**:
**Trello**:
**Figma**:
**Miro**:

## Environment Overview:

<H3>infrastructure architect</H2>
<p><img align="left" src="https://i.ibb.co/W0h5npz/infrastructure.png" alt="infra" /></p>
<H3>Local Environment</H3>
<dl>
  <dt>on Hypervisor (private cloud computing)</dt>
  <dd>- main storage of WP main state</dd>
  <dd>- control access for permission of edit the backend storage</dd>
  <dd>- implement and store the php</dd>
  <dd>- store the main data that have huge size</dd>
</dl>
<H3>Hosted Environment</H3> 
<dl>
  <dt>AWS<dt>
  <dd>- EC2</dd>
  <dd>- S3</dd>
  <dd>- Direct connection / networking interface</dd>
  <dt>X10</dt>
  <dd>- staging/redundant</dd>
</dl>
<p>EC2: the hypervisor for instance machine to run the WP main system environment.</p>
<p>S3: the storage for store the main database of the WP system like admin system and some main content that have priority.</p>
<p>Direct connection / networking interface: the network for connect between the backend and bgp redirect to redundant page, also provide the navigate WP to internet.</p>
<p>X10: the external free hosting, using for deploy the WP from oversea cause AWS policy. the X10 have advantage on one click building the WP infrastructure and there are unlimited on bandwidth and data transfer.</p>
## version control
<p>git version control: manage the file on the hypervisor to commit the GitHub.</p>
<p>ssh command: the ssh command control the backend os to config the environment and system co-operate(linux, aws console)</p>
## testing
<p>hard-case testing: test on the many envirionment device.</p>
<p>test load: test the package of visual demand access the site.</p>
<p>staging site: create the mirror of the site to prevent the workload of the main site down(can be tested on prototype function).</p>
## automation
<dl>
  <dd>- the appointment system</dd>
  <dd>- the BGP navigate</dd>
</dl>
