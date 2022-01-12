# KSES TODO


* ~~create a nice homepage with interactive kses testing~~ GitHub will do for now.

* remove stuff in between &lt;script>..&lt;/script> and &lt;style>..&lt;/style>

* better fix for chr(173) bug and try to allow for "style:" where people
  want to use it

* XHTML tags of the style &lt;br/> instead of &lt;br />
  This is related to a small bug with &lt;a href="blah />
  Solution: rewrite parser.

* more attribute value checks. A better way of establishing value checks.

* more types of hooks, more accurately a better way of having multiple hooks,
  and ordering them

* return array of removed elements and attributes

* give the option of turning unacceptable elements to entities instead of
  removing them (and turn unacceptable attributes to their own tag, which is
  then turned to entities?) .. perhaps turn to comments as well?

* ">" in HTML tags
  &lt;img src="blah.gif" alt="x > 5">
  Not very important, but...

* make document well-formed... there is HTML Tidy, but perhaps we want this
   functionality in kses itself?

* lots of testing

* write better documentation

* feedback from users
