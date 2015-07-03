			<header>
				<h2 class="alt">Your <strong>Schedule</strong></h2>
				<p>Week: <span class='blue-text'><?php echo date('d/m/Y', strtotime($weekStart))
					   . " - " . date('d/m/Y', strtotime($weekStart .' + 1 week')); ?></span>
				</p>
			</header>
			
			<table class="6u center size-table">
				<caption><h3><?php echo $officer_schedule[0]['location'] ." ".
					 $officer_schedule[0]['shift']; ?>
				<thead>
					<tr>
						<th>Day</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>	
					<?php foreach ($days as $day => $status): ?>
				    <tr>
						<td class="t25"><?php echo $day;?></td>				
						<td class="t40">
							<?php if (!empty($status))
									echo "Workday";
								  else
								  	echo "-- Offday --";
							?></td>		
					</tr>
					<?php endforeach ?>
				</tbody>
			</table>

			<footer>
				<a href="<?php echo base_url("$designation/activity_report"); ?>" class="button scrolly">Start Shift</a>
			</footer>

		</div>
	</section>
</div>