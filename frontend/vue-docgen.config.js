module.exports = {
  componentsRoot: 'src/components',
  components: '**/[A-Z]*.vue',
  outDir: 'docs/components',
  apiOptions: {
    jsx: true
  },
  getDestFile: (file, config) => {
    const name = file.split('/').pop().replace('.vue', '')
    return `${config.outDir}/${name}.md`
  },
  templates: {
    component: require('./docs-templates/component.js'),
    events: require('./docs-templates/events.js'),
    props: require('./docs-templates/props.js'),
    slots: require('./docs-templates/slots.js')
  }
}