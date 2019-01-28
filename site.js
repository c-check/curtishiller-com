Vue.component('tweet', {
  props: ['data'],
  template: 
    `<div>
      <div class="them" v-if="data.user">
        <a v-bind:href="data.user.link">@{{ data.user.name }}</a> 
        ({{ data.user.full_name }}):
      </div>
      <div class="text" v-html="data.text"></div>
      <div v-if="data.media.url" class="media">
        <a v-bind:href="data.media.url"><img v-bind:src="data.media.image" /></a>
      </div>
    </div>`
});

Vue.component('insta-image', {
  props: ['data'],
  template:
    `<div>
      <img v-for="image in data.images" v-bind:src="image" />
    </div>`
});

Vue.component('insta-video', {
  props: ['data'],
  template:
    `<div>
      <video v-for="video in data.videos" v-bind:src="video" />
    </div>`
});

Vue.component('swarm', {
  props: ['data'],
  template:
    `<div>
      <div class="location">{{ data.checkin.location }}</div>
      <div v-if="data.checkin.text" class="text">{{ data.checkin.text }}</div>
      <img v-for="image in data.images" :src="image" />
    </div>`
});

Vue.component('item', {
  props: {
    date: String,
    item: Object
  },
  template:
    `<div v-bind:class="item.type">
       <div class="date">{{ date }}</div>
       <component v-bind:is="item.type" v-bind:data="item.data"></component>
    </div>`
});

var siteData = { items: [] };
var site = new Vue({
  el:   '#site',
  data: siteData
});

async function init(){
  let response = await fetch('http://curtishiller.com/api/v1/posts.php');
  let data = await response.json();
  
  for(var i in data){
    siteData.items.push(data[i]);
  }
}

init();

