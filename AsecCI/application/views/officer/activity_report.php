	    <style> /* I had to do this */
			#new-report { display: <?php echo $display_create; ?>; }
			#not-new {	display: <?php echo $display_report; ?>; }
			#incidents { display: <?php echo $display_incidents; ?>; }
		</style>
			<header>
				<h2 class="alt">Your Activity <strong>Report</strong></h2>
			</header>
			<section id="new-report" class="6u 12u$(mobile) center">
				<?php echo form_open("$designation/new_activity_report") ?>
				    <label><input type="text" name="prevID" placeholder="Previous Officer ID" class="size-input"></label>					
					<input type="submit" name="submit" value="Create New Report">
				</form>
			</section>
			<section id="not-new">
				<span>Report Details</span>
				<hr>
				<section id="report">
					<p>
					Time Started (in): <span class="blue-text"><?php echo $report['date_timeIn'];?></span><br>
					Shift: <span class="blue-text"><?php echo $report['shift'];?></span><br>
					Previous Officer: <span class="blue-text"><?php echo "$previous_officer_name 
								($report[previous_officer_id])";?></span></p>
				</section>
				<section id="incidents">
					<div class="10u 12u$(mobile) center">
						<?php foreach ($incidents as $incident):?>
							<article>
								<header>
									<h4>Incident: <span class="blue-text"><?php echo $incident['incident_type'];?></span></h4>
									<span>Incident Time: <span class="blue-text"><?php echo $incident['incident_time'];?></span></span>
								</header>
								<p><?php echo $incident['entry_report'];?></p>
							</article>
						<?php endforeach ?>
					</div>
				</section>
				<hr id="incidents"><br>
				<section class="6u 12u$(mobile) center">
					<?php echo form_open("$designation/activity_report") ?>
					    <label><input type="text" name="incident-type" placeholder="Incident" class="size-input"></label>
					    <label><textarea name="incident-details" placeholder="I found a missing dog" class="size-input"></textarea></label><br>
					    <input type="submit" name="submit" value="Add Incident">
					</form>
				</section>
				<hr><br>
				<section id="next-officer" class="6u 12u$(mobile) center">
					<?php echo form_open("$designation/close_activity_report") ?>
					    <label><input type="text" name="nextID" placeholder="Next Officer ID" class="size-input"></label>					
						<input type="submit" name="submit" value="Close Report">
					</form>
				</section>
			</section>
		</div>
	</section>
</div>