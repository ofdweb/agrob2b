$.fn.extend({
    treed: function (o) {
      
		var openedClass = 'glyphicon-minus-sign';
		var closedClass = 'glyphicon-plus-sign';

		if (typeof o != 'undefined'){
			if (typeof o.openedClass != 'undefined'){
				openedClass = o.openedClass;
			}
			if (typeof o.closedClass != 'undefined'){
				
			}
		};
      
        //initialize each of the top levels
        var tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
            var branch = $(this); //li with children ul
            //branch.prepend("<i style='cursor: pointer;' class='cat-name indicator glyphicon " + closedClass + "'></i>");
			
            branch.addClass('branch');
			
			/*
			branch.find('.cat-name').on('click', function(e){
				if (this == e.target) {
					var li = $(this).closest("li");
                    var icon = li.find('i:first');
                    icon.toggleClass(openedClass + " " + closedClass);
                    //li.children().children().toggle();
					li.find('ul').toggle();
                }
			});
			*/
			
			/*
            branch.on('click', function (e) {
                if (this == e.target) {
                    var icon = $(this).children('i:first');
                    icon.toggleClass(openedClass + " " + closedClass);
                    $(this).children().children().toggle();
                }
            })
			*/
            //branch.children().children().toggle();
            //branch.find('ul').toggle();
        });
        //fire event from the dynamically added icon
      tree.find('.branch .indicator').each(function(){
        $(this).on('click', function () {
            $(this).closest('li').click();
        });
      });
        //fire event to open branch if the li contains an anchor instead of text
        tree.find('.branch>a').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
        //fire event to open branch if the li contains a button instead of text
        tree.find('.branch>button').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
    }
});