<?php
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/inc/astra_responsive.php';

final class ResponsiveTest extends TestCase {
	public function testScriptChoisitLaClasseAvantLeBundleSansDependance(): void {
		foreach ([[astra_responsive_seuil_entete(), '921.99'], [800, '800.99']] as [$seuil, $borne]) {
			foreach ([true, false] as $mobile) {
				$script = astra_responsive_script_precoce($seuil);
				$programme = 'const assert=require("node:assert/strict"),vm=require("node:vm");'
					. 'const classes=new Set(["ast-desktop","site-exemple"]);'
					. 'vm.runInNewContext(' . json_encode($script) . ',{window:{matchMedia:q=>{assert.equal(q,'
					. json_encode("(max-width:{$borne}px)") . ');return{matches:' . ($mobile ? 'true' : 'false') . '}}},'
					. 'document:{body:{classList:{add:c=>classes.add(c),remove:c=>classes.delete(c)}}}});'
					. 'assert.deepEqual([...classes].sort(),' . json_encode([$mobile ? 'ast-header-break-point' : 'ast-desktop', 'site-exemple']) . '.sort());';
				$processus = proc_open(['node', '-e', $programme], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
				fclose($pipes[0]);
				$sortie = stream_get_contents($pipes[1]);
				$erreur = stream_get_contents($pipes[2]);
				fclose($pipes[1]); fclose($pipes[2]);
				$this->assertSame(0, proc_close($processus), $sortie . $erreur);
			}
		}
	}

	public function testMenuDuPanneauReprendLaSourceDuMenuPrincipal(): void {
		$c = astra_hfb_normaliser(['elements' => ['entete' => ['menu-1' => ['source' => 'rubrique', 'id' => 42]]]]);
		$this->assertSame([['id' => 'mobile-menu', 'numero' => 1, 'source' => 'rubrique', 'id_source' => 42, 'accueil' => 'oui']], astra_responsive_menus_sans_js($c));
	}

	public function testMenusMobilesDedoublonnesEtOrdreVisuel(): void {
		$c = astra_hfb_normaliser(['entete_mobile' => ['above' => ['above_center' => ['menu-2']], 'primary' => ['primary_right' => ['mobile-trigger', 'menu-2']]]]);
		$menus = astra_responsive_menus_sans_js($c);
		$this->assertSame(['menu-2', 'mobile-menu'], array_column($menus, 'id'));
		$this->assertSame([2,1], array_column($menus, 'numero'));
	}

	public function testAucunMenuMobileNeCreeDeRepli(): void {
		$c = astra_hfb_normaliser([]);
		foreach ($c['entete_mobile'] as &$zones) { foreach ($zones as &$elements) { $elements = []; } unset($elements); } unset($zones);
		$this->assertSame([], astra_responsive_menus_sans_js($c));
	}
}
