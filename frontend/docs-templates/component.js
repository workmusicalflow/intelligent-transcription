module.exports = (renderedUsage, doc) => {
  const { displayName, description, docsBlocks, props, events, slots, methods } = doc
  
  return `# ${displayName}

${description || ''}

## Usage

\`\`\`vue
${renderedUsage.props}
\`\`\`

${docsBlocks ? docsBlocks.map(block => `${block}`).join('\n\n') : ''}

${props && props.length > 0 ? `## Props

${props.map(prop => `### ${prop.name}

- **Type:** \`${prop.type ? prop.type.name : 'unknown'}\`
- **Required:** ${prop.required ? 'Yes' : 'No'}
- **Default:** \`${prop.defaultValue ? prop.defaultValue.value : 'undefined'}\`

${prop.description || ''}`).join('\n\n')}` : ''}

${events && events.length > 0 ? `## Events

${events.map(event => `### ${event.name}

${event.description || ''}

**Payload:** \`${event.type ? event.type.names.join(' | ') : 'unknown'}\``).join('\n\n')}` : ''}

${slots && slots.length > 0 ? `## Slots

${slots.map(slot => `### ${slot.name}

${slot.description || ''}

**Scoped:** ${slot.scoped ? 'Yes' : 'No'}`).join('\n\n')}` : ''}

${methods && methods.length > 0 ? `## Methods

${methods.map(method => `### ${method.name}

${method.description || ''}

**Parameters:**
${method.params ? method.params.map(param => `- \`${param.name}\` (${param.type ? param.type.name : 'unknown'}): ${param.description || ''}`).join('\n') : 'None'}

**Returns:** \`${method.returns ? method.returns.type.name : 'void'}\``).join('\n\n')}` : ''}

---

*Generated automatically by vue-docgen-cli*`
}