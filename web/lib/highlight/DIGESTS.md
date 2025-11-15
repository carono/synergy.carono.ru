## Subresource Integrity

If you are loading Highlight.js via CDN you may wish to use [Subresource Integrity](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity) to guarantee that you are using a legimitate build of the library.

To do this you simply need to add the `integrity` attribute for each JavaScript file you download via CDN. These digests are used by the browser to confirm the files downloaded have not been modified.

```html
<script
  src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"
  integrity="sha384-5xdYoZ0Lt6Jw8GFfRP91J0jaOVUq7DGI1J5wIyNi0D+eHVdfUwHR4gW6kPsw489E"></script>
<!-- including any other grammars you might need to load -->
<script
  src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/languages/go.min.js"
  integrity="sha384-HdearVH8cyfzwBIQOjL/6dSEmZxQ5rJRezN7spps8E7iu+R6utS8c2ab0AgBNFfH"></script>
```

The full list of digests for every file can be found below.

### Digests

```
sha384-4OPZSHQbxzPqFMOXnndxQ6TZTI/B+J4W9aqTCHxAx/dsPS6GG25kT7wdsf66jJ1M /es/languages/php.js
sha384-VxmvZ2mUpp1EzFijS40RFvIc7vbv/d5PhMxVFG/3HMpVKD4sVvhdV9LThrJDiw9e /es/languages/php.min.js
sha384-0XBmTxpMLuDjB2zdfbi3Lv4Yokm2e1YFGZ9mCmI5887Kpi23jMF5N7rPrf0GdoU/ /languages/php.js
sha384-Bv/Sxv6HlOzYOdV1iQpJTG3xiqGWIIMq9xsFfEX8ss7oNWMgKqOa/J2WSFG2m7Jd /languages/php.min.js
sha384-ZlnC1XmD3b9LDv2bDXwmvyFy1HqjpOSyQ72clSESEvQQy/+4nYBPp23Driq7ukFP /highlight.js
sha384-hvl7K0acK7zk1HiIwSTI6edxM1eZ3vOxEvs3kWdOf+lYZZrmuEDbZEFXbb3/JO5k /highlight.min.js
```

