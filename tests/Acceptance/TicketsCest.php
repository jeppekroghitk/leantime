<?php

namespace Acceptance;

use Codeception\Attribute\Depends;
use Tests\Support\AcceptanceTester;
use Tests\Support\Page\Acceptance\Login;

class TicketsCest
{
    public function _before(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login('test@leantime.io', 'test');
    }

    public function createTicket(AcceptanceTester $I)
    {
        $I->wantTo('Create a ticket');

        $I->amOnPage('/tickets/showKanban#/tickets/newTicket');
        echo $I->grabPageSource();
        $I->waitForElementVisible(".main-title-input", 120);
        $I->fillField(["class" => "main-title-input"], 'Test Ticket');
        $I->click('.tagsinput');
        $I->type('test-tag,');
        $I->click('.mce-content-body');
        $I->waitForElementClickable('#ticketDescription_ifr', 120);
        $I->switchToIFrame('#ticketDescription_ifr');
        $I->waitForElementVisible("#tinymce", 120);
        $I->wait(5);
        $I->click("#tinymce");
        $I->type('Test Description');
        $I->switchToIFrame();
        $I->waitForElementClickable('//*[@id="ticketdetails"]//input[@name="saveTicket"][@type="submit"]', 120);
        $I->click('//*[@id="ticketdetails"]//input[@name="saveTicket"][@type="submit"]');
        $I->waitForElement('.growl', 120);
        $I->seeInDatabase('zp_tickets', [
            'id' => 10,
            'headline' => 'Test Ticket',
            'description like' => '%<p>Test Description</p>%',
        ]);
    }

    #[Depends('createTicket')]
    public function editTicket(AcceptanceTester $I)
    {
        $I->wantTo('Edit a ticket');

        $I->amOnPage('/tickets/showKanban#/tickets/showTicket/10');
        $I->waitForElementVisible(".main-title-input", 120);
        $I->click('.mce-content-body');
        $I->waitForElementClickable('#ticketDescription_ifr', 120);
        $I->switchToIFrame('#ticketDescription_ifr');
        $I->waitForElementVisible("#tinymce", 120);
        $I->wait(5);
        $I->click("#tinymce");
        $I->type('Test Description Edited');
        $I->switchToIFrame();
        $I->waitForElementClickable('//*[@id="ticketdetails"]//input[@name="saveTicket"][@type="submit"]', 120);
        $I->click('//*[@id="ticketdetails"]//input[@name="saveTicket"][@type="submit"]');
        $I->waitForElement('.growl', 60);
        $I->wait(2);
        $I->see('To-Do was saved successfully');
    }
}
