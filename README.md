# Dynamic Scheduler

The dynamic scheduler allows you to schedule events "at runtime" in code instead of having to hardcode them in advance.
It works by providing a wrapper (proxy) that goes around a `Schedule` instance.

Every call you perform after that is logged and stored in the `ScheduledTask`-instance.

This package uses so called _super closures_ (serializable closures). These are often frowned upon, but since 2 out of 3 of the possible ways to schedule an event involve a closure, it would be hard to provide enough functionality without. 

I know this thing can be improved in a lot of areas, but I needed the functionality fast and wanted to share the idea.
Feel free to create issues or PRs if you feel you can contribute or improve.

Note: The TextToSchedule command does not support parameters to functions yet. These should be added soon.

## Why?
 
I get that this seems fairly useless under normal circumstances. 
I used it to be able to schedule commands received via chat. 
It allows my users to say things like: `every 10 minutes: command` or `weekdays, every 30 minutes: that other command` 
Using the TextToSchedule component this allows me to dynamically build those things and add them to the Laravel schedule functionality as if they were hardcoded.

## How?

We start of by calling the proxy function: 

``` 
$scheduler = ScheduleProxy::proxy();
```

You can either specify your own `Schedule` instance or one will be created for you.

With that new instance you can create events and schedule them just like you normally would:
```
$scheduler->command('emails:send --force')->weekdays()->at('19:30');
```

When you're done you can extract a new `ScheduledTask`-issue and save it:
```
with($scheduler->getTask())->save();
```

From then on out it will by added dynamically to your schedule. 
That's all there is to it.

## And how do I use text?

The method is similar, I usually create the event I want manually. After that I allow the text interpreter to add the scheduling:

```
$scheduler = ScheduleProxy::proxy();
$scheduler->job(MyDynamicallyCalledJob::class);
with(new TextToSchedule($scheduler))->apply('weekdays, every 10 minutes');
with($scheduler->getTask())->save();
```

The parser splits statements by `,` or `;` and considers each separately.
Using the `intl` php extension it can convert integer values in their text representation, so `every 10 minutes` will also result in a call to `everyTenMinutes()`. 

This also works on a regular schedule event by the way, should that be of any use to you.

As mentioned at the top, there is no support for parameters yet, but I plan on adding that asap.
