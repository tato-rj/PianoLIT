<section>
	<p class="bold">LESSONS OVERVIEW</p>
	@php($weeks = carbon($data['start_month'] . '-1-' . $data['years'][0])->diffInWeeks(carbon($data['end_month'] + 1 . '-1-' . $data['years'][1])))
	<p>Piano students receive {{$weeks - $data['vacation_weeks'] - $data['makeup_weeks']}} scheduled weekly lessons from {{getMonthName($data['start_month'])}} through {{getMonthName($data['end_month'])}}, {{$data['group_classes']}} group studio
		classes, and one formal studio recital. Please see the Studio Calendar for this year’s complete list of
	scheduled lessons, classes, and recitals. Lessons are 30, 45, or 60 minutes long.</p>

	<p class="bold">PRACTICE</p>
	<p>Daily practice is essential and expected. Playing piano is a physical, mental, and creative exercise. Regular
		practice (including repetition) leads to physical mastery, which in turn allows us to express our musical ideas.
		At the end of each lesson, students have a specific assignment for daily practice, detailed in the assignment
		notebook. Practice assignments are carefully crafted in response to the successes and struggles of the lesson
		as well as the strengths and learning style of the individual student.
		The best indicator of enjoyment of a new activity is the feeling of growth. Nothing beats the feeling of
		having mastered something new and challenging. Students of all ages can tell when they’re not progressing,
		and the result of not practicing is a steep decline into boredom. Students can also spot empty praise a mile
		away — so I don't give it. I fine-tune assignments that I believe are just challenging enough to help keep
		students on a roll of success, thereby maintaining their interest in music. Students who practice regularly stay
		in the zone of being able to master each assignment, and they enjoy piano because they feel they are
	excelling.</p>

	<p class="bold">MATERIALS</p>
	<p>Each student uses a set of 3-4 music books and an assignment notebook. I purchase the materials in
		advance and include the cost in the monthly invoice. The assignment notebook is our main point of contact.
		It details the practice assignment in manageable steps, provides recommendations for easier practice, and
		has a space for the student/parent to log practice. Students should always look at their assignment
	notebook.</p>

	<p class="bold">EXPECTATIONS</p>
	<p>Daily Practice: see notes on practice above, and make it a good habit!
		Attendance: Regular, on-time attendance with a polished assignment.
		Hands Ready to Play: Nails should be kept short to ensure proper technique. Playing with long nails is like
	trying to run in high-heeled shoes! Students always wash hands before the lesson.</p>

	<p class="bold">PARENTAL INVOLVEMENT</p>
	<p>In the lesson: Parents may attend the lesson or not. If you’re not sure, my recommendation is not to attend,
		as students tend to focus and be more creative with fewer adult observers. With that said, parents and the
		occasional visitor (such as a visiting grandparent) are welcome.
		At home: Help instill a good practice habit from the get-go by making it a routine. Supervision isn’t
		necessary, but provide the quiet time and ensure that the assignment notebook is being used. For older
		students, as activities and school demands increase, help your child continue to keep their practice routine.
		Remember that the biggest indicator of enjoyment is the feeling of growth and achievement. It’s very
	important that older students feel they are moving past the “baby stuff.”</p>
</section>