<a class="nav-link" href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
	<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>Log out
</a>