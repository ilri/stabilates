var w = 500,
    h = 500,
    node,
    link,
    root;

function startVis(json){
   force = d3.layout.force()
      .on("tick", tick)
      .charge(function(d) { return d._values ? -d.size / 100 : -30; })
      .linkDistance(function(d) { return d.target._values ? 80 : 30; })
      .size([w, h - 160]);

   vis = d3.select("#stab_history").append("svg:svg")
      .attr("width", w)
      .attr("height", h);

   //append an info div
   $('svg').before("<div id='stab_info'>Move the mouse over a stabilate to display its information</div>");
   root = json;
   root.fixed = true;
   root.x = w / 2;
   root.y = h / 2 - 80;
   update();
}

function update() {
  var nodes = flatten(root),
      links = d3.layout.tree().links(nodes);

  // Restart the force layout.
  force
      .nodes(nodes)
      .links(links)
      .start();

  // Update the links…
  link = vis.selectAll("line.link")
      .data(links, function(d) { return d.target.id; });

  // Enter any new links.
  link.enter().insert("svg:line", ".node")
      .attr("class", "link")
      .attr("x1", function(d) { return d.source.x; })
      .attr("y1", function(d) { return d.source.y; })
      .attr("x2", function(d) { return d.target.x; })
      .attr("y2", function(d) { return d.target.y; });

  // Exit any old links.
  link.exit().remove();

  // Update the nodes…
  node = vis.selectAll("circle.node")
      .data(nodes, function(d) { return d.id; })
      .style("fill", color);

  node.transition()
      .attr("r", function(d) { return d.children ? 4.5 : Math.sqrt(d.size) / 10; });

  // Enter any new nodes.
  node.enter().append("svg:circle")
      .attr("class", "node")
      .attr("cx", function(d) { return d.x; })
      .attr("cy", function(d) { return d.y; })
      .attr("r", function(d) { return d.children ? 4.5 : Math.sqrt(d.size) / 10; })
      .style("fill", color)
      .on("click", click)
      .on("mouseover", hover)
      .call(force.drag);

  // Exit any old nodes.
  node.exit().remove();
}

function tick() {
  link.attr("x1", function(d) { return d.source.x; })
      .attr("y1", function(d) { return d.source.y; })
      .attr("x2", function(d) { return d.target.x; })
      .attr("y2", function(d) { return d.target.y; });

  node.attr("cx", function(d) { return d.x; })
      .attr("cy", function(d) { return d.y; });
}

// Color leaf nodes orange, and packages white or blue.
function color(d) {
  return d._children ? "#3182bd" : d.children ? "#c6dbef" : "#fd8d3c";
}

// Toggle children on click.
function click(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  } else {
    d.children = d._children;
    d._children = null;
  }
  update();
}

//display the stabilate info when one hovers the mouse
function hover(d){
   var children = (d.children !== undefined) ? d.children.length : 0;
   var content = sprintf("<table>\n\
      <tr><th colspan='2'>Stabilate Info</th></tr>\n\
      <tr><td><dl class='dl-horizontal'><dt>Stabilate Name:</dt><dd>%s</dd></dl></td><td><dl class='dl-horizontal'><dt>Total Passages:</dt><dd>%s</dd></dl></td></tr>\n\
      <tr><td><dl class='dl-horizontal'><dt>Parent Stabilate:</dt><dd>%s</dd></dl></td><td><dl class='dl-horizontal'><dt>Children:</dt><dd>%s</dd><dl></td></tr>\n\
      </table>", d.name, d.passages, d.parent, children);
   $('#stab_info').html(content);
}

// Returns a list of all nodes under the root.
function flatten(root) {
  var nodes = [], i = 0;

  function recurse(node) {
    if (node.children) node.size = node.children.reduce(function(p, v) { return p + recurse(v); }, 0);
    if (!node.id) node.id = ++i;
    nodes.push(node);
    return node.size;
  }

  root.size = recurse(root);
  return nodes;
}