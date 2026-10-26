Setup Notes
===========

1) Google Analytics (GA4)
   - Measurement ID embedded: G-TM489P5Y7S
   - Confirm data in GA Realtime after deployment.

2) Google Tag Manager (GTM)
   - Replace both 'GTM-XXXXXXX' occurrences in index.html (head + noscript).
   - In GTM, add GA4 Configuration tag with Measurement ID G-TM489P5Y7S, publish, then (optionally) remove direct GA from index.html.

3) Google Search Console
   - Meta tag placeholder in index.html:
     <meta name="google-site-verification" content="REPLACE_WITH_SEARCH_CONSOLE_TOKEN">
   - Or rename google-verification.html with Google's filename and contents.

4) Bing Webmaster
   - Replace <meta name="msvalidate.01" content="REPLACE_WITH_BING_VERIFICATION_CODE"> with your code.

5) Facebook Pixel
   - Replace REPLACE_WITH_PIXEL_ID with your Pixel ID in the head script.

6) Sitemap
   - Submit https://www.mykaytech.com/sitemap.xml in Search Console.

7) Hosting
   - Requires PHP for contact.php to send email. Consider SMTP/PHPMailer if host blocks mail().