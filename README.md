## WARNING: Experimental

## Eager loading for SilverStripe DataLists

This module aims to solve the [N+1 problem](https://stackoverflow.com/questions/97197/what-is-the-n1-select-query-issue)
often encountered in the SilverStripe ORM when relying on several levels of data. For instance, in the following model:

```
Team:
  has_one: Schedule
Schedule:
  has_many: Matches
Matches:
  many_many: Attendees
```

It would not be atypical to want a view that rendered something like this:

```
<% loop $Teams %>
$Title
	<% with $Schedule %>
		$Title
		<% loop $Matches %>
			$Score
			Attendees
			<% loop $Attendees %>
				$Name
			<% end_loop %>
		<% end_loop %>
	<% end_with %>
<% end_loop %>
```

If there are 50 teams and each team has 10 matches, this results in **601 queries**.

The number of queries increases exponentially as the schedule gets more `Matches`.

While `has_one` relationships are cached once fetched, plural relationships are not, and we owe 
most of the unnecessary queries to that missing piece.

## Usage

```php
EagerLoadList::create(Team::class)
	->eagerLoad('Schedule')
		->eagerLoad('Matches')
			->eagerLoad('Attendees')
			->end()
		->end()
	->end()
	->eagerLoad('Owner')
	->end();
```

This eagerly loads the relationships and puts them into memory, applying them to dataobject instances as they are created.
For `has_one` relationships, this uses the inbuilt `setComponent` function, but for plural relationships, you'll
have to implement a setter, e.g:

```php
public function setAttendees(array $attendees)
{
	$this->cachedAttendees = ArrayList::create($attendees);

	return $this;
}

public function Attendees()
{
	if ($this->cachedAttendees) {
		return $this->cachedAttendees;
	}

	return parent::Attendees();
}
```
