<?php

include("header.html");
include("analytics.html");
?>
	<body>
		<table border=0 width="100%" style="margin-left: 10px"><tr><td width="50"><a href='javascript:location.reload(true)'><img id='logo' height=50 width=50 src='120.png' alt='JKK logo'/></a></td><td><span id="header">JKK agenda</span></tr></table>
<?php

error_reporting(0);
ini_set("display_errors", 0);

function GetDeltaTime($dtTime1, $dtTime2)
{
  $nUXDate1 = strtotime($dtTime1);
  $nUXDate2 = strtotime($dtTime2);

  $nUXDelta = $nUXDate1 - $nUXDate2;
  $strDeltaTime = "" . $nUXDelta/60/60; // sec -> hour

  $nPos = strpos($strDeltaTime, ".");
  if ($nPos !== false)
    $strDeltaTime = substr($strDeltaTime, 0, $nPos + 3);

  return $strDeltaTime;
}

require 'class.iCalReader.php';

date_default_timezone_set('Europe/Stockholm');

echo "<div id='updated'>Inl&auml;st kl ".date("H:i:s")." - Visar tv&aring; veckor fram&ouml;ver</div>";

$ical = new ICal('http://www3.idrottonline.se/Calendar/ICalExport.aspx?calendarId=31199&activityTypeIds=&calendarName=Kalender&newcalendar=1');
//$ical = new ICal('ICalExport.ics');

if (!($events = $ical->events()))
{
	echo "<div class='day'><div class='event'>Det gick inte att l&auml;sa in aktiviteter fr&aring;n kalendern just nu. Anv&auml;nd kalendern p&aring; klubbens hemsida ist&auml;llet...</div></div>";
}
else
{
	$previousEventDate = "";
	$count = 0;

	$dayOfWeek[1] = "M&aring;ndag";
	$dayOfWeek[2] = "Tisdag";
	$dayOfWeek[3] = "Onsdag";
	$dayOfWeek[4] = "Torsdag";
	$dayOfWeek[5] = "Fredag";
	$dayOfWeek[6] = "L&ouml;rdag";
	$dayOfWeek[7] = "S&ouml;ndag";

	$monthOfYear[1] = "januari";
	$monthOfYear[2] = "februari";
	$monthOfYear[3] = "mars";
	$monthOfYear[4] = "april";
	$monthOfYear[5] = "maj";
	$monthOfYear[6] = "juni";
	$monthOfYear[7] = "juli";
	$monthOfYear[8] = "augusti";
	$monthOfYear[9] = "september";
	$monthOfYear[10] = "oktober";
	$monthOfYear[11] = "november";
	$monthOfYear[12] = "december";

	$daysFromNow = 0;
	$today = new DateTime; // Current date and time in server time zone
	$listDate = date_format($today, "Y-m-d");

	while($daysFromNow <= 14)
	{
		$count = 0;

		echo "<div class='date'>";

		if ($listDate == date("Y-m-d"))
			echo "Idag, ".$dayOfWeek[date_format($today, "N")];
		else if (GetDeltaTime($listDate, date("Y-m-d")) >= 0 && GetDeltaTime($listDate, date("Y-m-d")) <= 1*24)
			echo "Imorgon, ".$dayOfWeek[date_format($today, "N")];
		else if (GetDeltaTime($listDate, date("Y-m-d")) >= 0 && GetDeltaTime($listDate, date("Y-m-d")) <= 6*24)
			echo $dayOfWeek[date_format($today, "N")];
		else
			echo $dayOfWeek[date_format($today, "N")].", ".date_format($today, "d")." ".$monthOfYear[date_format($today, "n")];

		echo "</div>"; // End of "date"

		foreach ($events as $event)
		{
			$eventStartDate = date("Y-m-d", date_offset_get(new DateTime)+$ical->iCalDateToUnixTimestamp($event['DTSTART']));
			$eventEndDate = date("Y-m-d", date_offset_get(new DateTime)+$ical->iCalDateToUnixTimestamp($event['DTEND']));

			if (new DateTime($eventStartDate) <= new DateTime($listDate) && new DateTime($eventEndDate) >= new DateTime($listDate))
			{
				$count++;
				$eventTimeStart = date_offset_get(new DateTime)+$ical->iCalDateToUnixTimestamp($event['DTSTART']);
				$eventTimeStop = date_offset_get(new DateTime)+$ical->iCalDateToUnixTimestamp($event['DTEND']);
				if ($eventStartDate != $eventEndDate && $eventStartDate == $listDate)
				{
					$beginTime = date("H:i", $eventTimeStart);
					$endTime = "24:00";
				}
				else if ($eventStartDate != $eventEndDate && $eventEndDate == $listDate)
				{
					$beginTime = "00:00";
					$endTime = date("H:i", $eventTimeStop);
				}
				else if ($eventStartDate != $eventEndDate)
				{
					$beginTime = "00:00";
					$endTime = "23:59";
				}
				else
				{
					$beginTime = date("H:i", $eventTimeStart);
					$endTime = date("H:i", $eventTimeStop);
				}

				if ($eventTimeStop < time())
                                	echo "<div class='pastevent'>";
                        	else if ($eventTimeStart < time() && $eventTimeStop > time())
                                	echo "<div class='currentevent'>";
                        	else
                                	echo "<div class='day'>";

				//echo "<div class='day'>";
				echo "<div class='eventtime'>".$beginTime." - ".$endTime.", <span class='eventinfo'>".htmlentities(str_replace("\\n", " ", $event['SUMMARY']), ENT_QUOTES, "UTF-8")."</span></div>";
				$str = htmlentities(str_replace("\,", ",", str_replace("\\n", " ", $event['DESCRIPTION'])), ENT_QUOTES, "UTF-8");
				$str = preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $str);
				echo "<div class='eventdesc' id='eventdesc".$count."'>".$str."</div>";
				echo "<div class='eventdesc'>Plats: ".$event['LOCATION']."</div>";
				echo "</div>"; // End of "day"
				echo "</div>"; // End of "pastevent" or "currentevent"
			}
		}
		if ($count == 0)
			echo "<div class='missing'>Aktiviteter saknas.</div>";

		$listDate = date_format($today->add(new DateInterval("P1D")), "Y-m-d");
		$daysFromNow = $daysFromNow + 1;
	}
}

?>
		<div>&nbsp;</div>
<div class="footer" id="disclaimer">Informationen i agendan h&auml;mtas i realtid fr&aring;n kalendern p&aring; klubbens hemsida. Vid eventuella tveksamheter kring riktigheten i informationen, s&auml;kerst&auml;ll informationen genom att kontrollera klubbens officiella kalender.</div></div>
		<div>&nbsp;</div>
		<div class="footer"><a href="javascript:void(0)" onclick="document.getElementById('qroverlay').style.visibility='visible'">Visa QR-kod</a></div>
		<div class="footer"><a href='http://www.j-kk.se'
target='_blank'>J&ouml;nk&ouml;pings Kl&auml;tterklubbs hemsida</a></div>
		<div class="footer"><a href='http://rcsport.se' target='_blank'>RC Sports hemsida</a></div>
		<div id="qroverlay"><div style="text-align: center; background-color: #ffffff; position: fixed; top: 50%; left: 50%; margin-top: -148px; margin-left: -148px" onclick="document.getElementById('qroverlay').style.visibility='hidden'"><img src="qr.svg"/></div></div>
		<div>&nbsp;</div>
		<div>&nbsp;</div>
<?php
include("footer.html")
?>
