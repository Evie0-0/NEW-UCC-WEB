<?php
use Core\Database;
use Repo\PublicCollegesRepository;

require_once __DIR__ . '/../../app/Repositories/PublicCollegesRepository.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$currentCollege = isset($_GET['college']) ? (string) $_GET['college'] : '';

$collegeRepository = new PublicCollegesRepository(Database::connect());


function navActive($pages, $currentPage) {
	return in_array($currentPage, (array) $pages, true) ? 'active' : '';
}


function navCurrent($pages, $currentPage) {
	return in_array($currentPage, (array) $pages, true) ? 'current-page' : '';
}


try {
	$colleges = $collegeRepository->getColleges();
} catch (Throwable $e) {
	error_log('Navbar colleges error: ' . $e->getMessage());
	$colleges = [];
}

$aboutPages = [
	'about.php',
	'board-of-regents.php',
	'executive-officials.php'
];

$aboutActive = in_array($currentPage, $aboutPages, true);
$academicsPages = [
	'colleges.php',
	'program-events.php',
	'colleges-events.php'
];
$academicsActive = in_array($currentPage, $academicsPages, true);
?>


<!-- BRAND HEADER -->
<header class="brand-header">
	<div class="container brand-inner">

		<div class="brand-logos">
			<img src="images/BagongPilipinas.png" alt="Bagong Pilipinas">
			<img src="images/Caloocan.png" alt="Caloocan City">
			<img src="images/UCC2.png" alt="University of Caloocan City seal">
		</div>

		<nav class="main-nav" id="mainNav">
			<div class="nav-inner">

				<div class="nav-links">
					<a href="homepage.php" class="<?= navActive('homepage.php', $currentPage) ?>">HOME</a>

					<div class="nav-dropdown <?= $aboutActive ? 'active' : '' ?>">

						<button type="button" class="<?= $aboutActive ? 'active' : '' ?>" aria-expanded="false">
							ABOUT <span>⌄</span>
						</button>


						<div class="dropdown-menu">
							<a href="about.php" class="<?= navCurrent('about.php', $currentPage) ?>">About UCC</a>
							<a href="board-of-regents.php" class="<?= navCurrent('board-of-regents.php', $currentPage) ?>">Board of Regents</a>
							<a href="executive-officials.php" class="<?= navCurrent('executive-officials.php', $currentPage) ?>">Executive Officials</a>
						</div>
					</div>


					<div class="nav-dropdown <?= $academicsActive ? 'active' : '' ?>">
						<button type="button" class="<?= $academicsActive ? 'active' : '' ?>" aria-expanded="false">
							ACADEMICS <span>⌄</span>
						</button>


						<div class="dropdown-menu academics-menu">
							<?php foreach ($colleges as $college): ?>
								<?php
									$collegeName = (string) $college['name'];
									$isCurrent = $currentPage === 'colleges.php' && $currentCollege === $collegeName;
								?>
								<a href="colleges.php?college=<?= urlencode($collegeName) ?>"
								   class="<?= $isCurrent ? 'current-page' : '' ?>">
									<?= htmlspecialchars($collegeName, ENT_QUOTES, 'UTF-8') ?>
								</a>
							<?php endforeach; ?>
						</div>

					</div>

					<a href="campus.php" class="<?= navActive('campus.php', $currentPage) ?>">CAMPUS</a>

					<a href="contacts.php" class="<?= navActive('contacts.php', $currentPage) ?>">CONTACTS</a>
				</div>

			</div>
		</nav>

		<img class="am-logo" src="images/AM-LOGO.png" alt="Aksyon at Malasakit">
	</div>
</header>