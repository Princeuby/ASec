<!-- Main -->
<div id="main">

	<!-- Intro -->
	<section id="top" class="one dark cover">
		<div class="container">

			<header>
				<h2 class="alt">Your Activity <strong>Report</strong></h2>
				<p>Don't  forget the small details that no one cares about</p>
			</header>
			
			<section class="6u center size-panel">
				<?php echo form_open('officer/activity_report') ?>
				    <label><input type="text" name="incident" placeholder="Incident" class="size-input"></label>
				    <label><textarea name="details" placeholder="I found a missing dog" class="size-inpu"></textarea></label><br>
				    <input type="submit" name="submit" value="Add">
				</form>
			</section>
			
		</div>
	</section>
</div>