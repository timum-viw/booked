<div class="dashboardBorder">
	<div id="announcementsHeader" class="dashboardHeader">
		<a href="javascript:void(0);" onclick="showHideDashboard('{$AnnouncementsId}')" title="{translate key='ShowHide'}">{translate key="Announcements"}</a>
	</div>
	<div id="{$AnnouncementsId}" style="display:{$AnnouncementsDisplayStyle};">
		<ul>
			{foreach from=$Announcements item=each}
			    <li>{$each}</li>
			{foreachelse}
				{translate key="NoAnnouncements"}
			{/foreach}
		</ul>
	</div>
</div>