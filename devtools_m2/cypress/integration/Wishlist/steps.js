import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function () {
    cy.contains('Create an Account').click()
    cy.get('#form-validate').within(function () {
        cy.get('input[name="firstname"]').type('Test')
        cy.get('input[name="lastname"]').type('User')
        cy.get('input[name="email"]').type('testuser@example.com')
        cy.get('input[name="password"]').type('blahblah123!!!')
        cy.get('input[name="password_confirmation"]').type('blahblah123!!!')
        cy.contains('Create an Account').click()
    })
})

When('I add a {string} widget to my wishlist', function (type) {
    cy.visit(`/widget-1.html`)
    switch (type) {
        case 'configurable':
            cy.get('#product-options-wrapper select').select('XL')
            break;
        case 'grouped':
            cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
            cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
            break;
        case 'bundle':
            // The pipe causes us to keep clicking until we get the stuff down below.
            const click = $el => $el.click()
            cy.contains('Customize and Add to Cart').should('be.visible').pipe(click).should(() => {
                const finalButton = Cypress.$('#product-addtocart-button')
                expect(finalButton).to.be.visible
            })
            break;
        case 'simple':
            // Do nothing
            break;
        default:
            throw 'Methinks thou hast forgotten something…'
    }
    cy.get('a[data-action="add-to-wishlist"]').within(($el) => {
        cy.get($el).should("have.attr", "data-post")
        const dataPostAttr = JSON.parse($el.attr("data-post"))
        expect(dataPostAttr.data).to.not.be.undefined
        expect(dataPostAttr.data.uenc).to.not.be.undefined
        expect(dataPostAttr.data.product).to.not.be.undefined
        expect(dataPostAttr.action).to.eq('http://main.magento.localhost:3006/wishlist/index/add/')
        // just because the above exist, doesn't mean add-to-wishlist is done intializing...
        // we'll wait just a titch longer
        cy.wait(1000)
        cy.wrap($el).click()
    })
})

Then('A wishlist {string} event should be sent to Drip', function (type) {
    cy.log('Validating that the wishlist call has everything we need')
    cy.wrap(Mockclient.retrieveRecordedRequests({
        'path': '/v2/123456/events'
    })).then(function (recordedRequests) {
        expect(recordedRequests).to.have.length.of.at.least(1)
        let expectedAction = (type === 'add' ? 'Added item to wishlist' : 'Removed item from wishlist')
        const wishlistEvent = recordedRequests.find(request => JSON.parse(request.body.string).events[0].action === expectedAction)
        expect(wishlistEvent).to.not.be.undefined

        const event = JSON.parse(wishlistEvent.body.string).events[0]
        expect(event.email).to.eq('testuser@example.com')
        expect(event.properties.product_id).to.eq('1')
        expect(event.properties.categories).to.eq('')
        expect(event.properties.brand).to.eq(false)
        expect(event.properties.name).to.eq('Widget 1')
        expect(event.properties.price).to.eq(1122)
        expect(event.properties.currency).to.eq('USD')
        expect(event.properties.image_url).to.eq('http://main.magento.localhost:3006/pub/media/catalog/product/')
        expect(event.properties.source).to.eq('magento')
    })
})

When('I remove the {string} widget from my wishlist', function (type) {
    // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
    cy.on('uncaught:exception', (err, runnable) => {
        return false
    })
    cy.visit(`/wishlist/`)
    switch (type) {
        case 'configurable':
            cy.get('#product-options-wrapper select').select('XL')
            break;
        case 'grouped':
            cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
            cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
            break;
        case 'simple':
        case 'bundle': // For now, we only have one option for each bundle option, so we don't have to do anything.
            // Do nothing
            break;
        default:
            throw 'Methinks thou hast forgotten something…'
    }
    cy.get('a[title="Remove Item"]').first().within(($el) => {
        cy.get($el).should("have.attr", "data-post-remove")
        const dataPostAttr = JSON.parse($el.attr("data-post-remove"))
        expect(dataPostAttr.data.item).to.equal("1")
        expect(dataPostAttr.data.uenc).to.eq("")
        expect(dataPostAttr.action).to.eq('http://main.magento.localhost:3006/wishlist/index/remove/')
        // just because the above exist, doesn't mean remove-from-wishlist is done intializing...
        // we'll wait just a titch longer
        cy.wait(1000)
        cy.wrap($el).click({ force: true })
    })
})
