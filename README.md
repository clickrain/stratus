
# Stratus Craft CMS Plugin
> A plugin for syncing and display Stratus data and in a Craft website

## Prerequisites

### Stratus account

You will need an account on Stratus to use this plugin.  If you are a Click Rain client, please contact your account manager to get started. Otherwise, please [visit the Stratus help center](https://stratus.crunch.help/en/integrations/craft-cms) to get started.

## Installation

### Install the plugin on the Craft project

#### Craft Plugin Store

1. Navigate to the Craft CMS control panel and click on the Plugin Store tab.
2. Search for Stratus and click on the plugin.
3. Click on the Try button to install the plugin.

#### Composer

1. Navigate to the project root directory and run the following command.
	```bash
	composer require clickrain/stratus
	```

2. Use the Craft CLI to install the plugin.
	```bash
	./craft plugin/install stratus
	```

### Generate an API key and webhook secret

1. Navigate to the [Account Settings page](https://app.gostratus.io/account/settings) on Stratus and click on the [Integrations tab](https://app.gostratus.io/account/integrations).

2. Under Craft CMS, click on the Generate Keys button. A modal where you can optionaly provide a webhook destination URL will appear.

3. Click on the Generate button.  The API key and webhook secret (if you provided a webhook destination URL) will be generated and displayed. Make sure to copy these values to a safe place before closing the modal. You will not be able to retrieve the API key after closing the modal.

### Configure the plugin

You can configure the plugin in one of two ways. The first is to use the Craft control panel.  The second is to use environment variables. The latter is recommended for production environments.

1. Add the following environment variables to the `.env` file in the project root directory.
	```bash
	STRATUS_API_KEY=<API Key>
	STRATUS_WEBHOOK_SECRET=whsec_<Webhook Secret>
	```

If you did not provide a webhook URL when generating the API key, you will need to configure the Craft project to run the job queue automatically.  You may want to disable `runQueueAutomatically` by setting it to `false` in `config/general.php`.

1. Configure the Craft project to run the job queue automatically.  You may want to disable `runQueueAutomatically` by setting it to `false` in `config/general.php`.
2. Add a cron job or scheduled task to run `craft stratus/default/import`

## Usage

### Access entries or reviews globally
```twig
{# accessing a listing by its Stratus UUID #}
craft.stratus
	.listings({uuid: 'e529a4a3ee1f3a0a47292f391cdbebe74fa72ff2'})
	.one()

{# accessing a review by its Stratus UUID #}
craft.stratus
	.reviews({uuid: 'aec1873c02ecad673af91f2af0f4daaa66fa1887'})
	.one()
```
### Access reviews based on their parent listing UUID
```twig
{# by parent Stratus UUID #}
craft.stratus
	.reviews({listing: 'e529a4a3ee1f3a0a47292f391cdbebe74fa72ff2'})
	.all()
```
### Access reviews associated with an entry by a custom field
```twig
{# asuming the field is named reviews #}
entry.reviews
	.all()
{# access reviews for the first connected listing to the provided entry #}
entry.listings
	.one()
	.reviews
	.all()
```
### Eager loading
```twig
{# listings eager loading their reviews #}
craft.stratus
	.listings().with('reviews').all()

{# reviews eager loading their parent listings #}
craft.stratus
	.reviews().with('listing').all()
```


### Filter reviews
```twig
{# Get the reviews for the first listing
and eager load reviews. Also filter the
reviews to only ones that are greater
than 3 stars or recommended (facebook). #}
set reviews = entry.listings
	.with([
		['reviews', {
			rating: [4,5],
			recommends: [true],
		}]
	]).one()
```
```twig
{# Filter reviews by platform. supported
platforms include the following

	google
	facebook
	healthgrades
	google_play_store
	apple_app_store
	yelp
	tripadvisor
	bbb
	indeed
	glassdoor
	yellow_pages
    zocdoc
    vitals
    realself
    ratemds
    webmd
    zillow

#}
craft.stratus
	.reviews({platform: 'google'})
	.all()
```
### Display information about reviews
```twig
{% set listing = entry.listings.one() %}
{% for review in listing.reviews %}
	<dl class="card p-3">
		<dt>Platform Published Date</dt>
		<dd>{{ review.platformPublishedDate|date }}</dd>
		<dt>Author</dt>
		<dd>{{ review.author }}</dd>
		<dt>Rating</dt>
		<dd>{{ review.icons.rating|raw (review.rating|default('null')) }}</dd>
		<dt>Recommends</dt>
		<dd>{{ (review.recommends|default('null')) }}</dd>
		<dt>Review Content</dt>
		<dd>{{ review.content }}</dd>
		<dt>Platform</dt>
		<dd>
			<ul>
				<li>{{review.icons.platform|raw}}</li>
				<li>{{review.platformName}}</li>
				<li>{{ review.platform }}</li>
			</ul>
		</dd>
		<dt>Reviewable</dt>
		<dd>{{ review.reviewableName }} ({{ review.reviewableType }})</dd>
		<dt>UUIDs</dt>
		<dd>
			<ul>
				<li>{{ review.stratusUuid }} (review)</li>
				<li>{{ review.stratusParentUuid }} (parent listing)</li>
			</ul>
		</dd>
		<dt>Parent Listing</dt>
		<dd>{{ review.listing }}</dd>
	</dl>
{% endfor %}
```
### Display information about listings
```twig
{% set listing = entry.listings.one() %}
<dl class="card p-3">
	<dt>Name</dt>
	<dd>{{ listing.name }}</dd>
	<dt>Type</dt>
	<dd>{{ listing.type }}</dd>
	<dt>UUIDs</dt>
	<dd>
		<ul>
			<li>{{ listing.stratusUuid }} (listing)</li>
		</ul>
	</dd>
	<dt>Associated Reviews</dt>
	<dd>
		<ul>
			{% for review in listing.reviews %}
				<li>{{ review }}</li>
			{% endfor %}
		</ul>
	</dd>
	<dt>Max Rating Overall</dt>
	<dd>{{ listing.maxRating }}</dd>
	<dt>Average Rating Overall</dt>
	<dd>{{ listing.avgRating }}</dd>
	<dt>Ratings by Platform</dt>
	<dd>
		{% for rating in listing.getRatings %}
			<dl class="p-2">
				<dt>Platform Name</dt>
				<dd>{{ rating.name }}</dd>
				<dt>Average for Platform</dt>
				<dd>{{ rating.avg }}</dd>
				<dt>Maximum for Platform</dt>
				<dd>{{ rating.max }}</dd>
				<dt>Individual Connection Ratings</dt>
				<dd>{{ rating.ratings|json_encode }}</dd>
			</dl>
		{% endfor %}
	</dd>
</dl>
```
