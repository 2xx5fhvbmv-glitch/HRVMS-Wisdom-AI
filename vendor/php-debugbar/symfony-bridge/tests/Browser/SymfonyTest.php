<?php

namespace DebugBar\Bridge\Symfony\Tests\Browser;


class SymfonyTest extends AbstractBrowserTestcase
{
    public function testTwigCollector(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/demo/');

        // Wait for Debugbar to load
        $crawler = $client->waitFor('.phpdebugbar-body');
        usleep(1000);

        if (!$this->isTabActive($crawler, 'request')) {
            $client->click($this->getTabLink($crawler, 'request'));
        }

        $crawler = $client->waitForVisibility('.phpdebugbar-panel[data-collector=request]');

        $statements = $crawler->filter('.phpdebugbar-panel[data-collector=request] .phpdebugbar-widgets-key')
            ->each(function($node){
                return $node->getText();
            });

        $this->assertEquals('uri', $statements[0]);
    }
}
