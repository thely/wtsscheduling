<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package alexandria
 */
?>
<head>
	<link rel="stylesheet" type="text/css" href="js/fullcalendar.min.css" />
	<script type="text/javascript" src="/js/lib/jquery.min.js"></script>
	<script type="text/javascript" src="/js/lib/jquery-ui.custom.min.js"></script>
	<script type="text/javascript" src="/js/lib/moment.min.js"></script>
	<script type="text/javascript" src="/js/fullcalendar.min.js"></script>
	<script type="text/javascript" src="/js/gcal.js"></script>
	<script>
		$(document).ready(function() {
			$('#calendar').fullCalendar({
				events: { url 'https://www.google.com/calendar/feeds/engima.in.my.soup%40gmail.com/public/basic' },
				eventClick: function() {
					return false;
				}
				header: {
					left: "prev,next today",
					center: "title",
					right: "agendaWeek,month"
				}
			});
		});
	</script>
	<style>
		#calendar {
			max-width: 900px;
			margin: 0 auto;
		}
	</style>
</head>
<body>
	<div id='calendar'></div>
</body>

<?php

	get_footer(); 

?>
