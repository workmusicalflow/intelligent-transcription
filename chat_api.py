#!/usr/bin/env python3
"""
Chat API script for Intelligent Transcription
Handles chat conversations and summarization using OpenAI API
"""

import os
import sys
import json
import argparse
import openai
import logging
from datetime import datetime
from openai_cache_utils import extract_cache_metrics, log_cache_performance

# Setup logging
logging.basicConfig(
    filename='python_api.log',
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s'
)

def setup_api_key():
    """Get OpenAI API key and organization ID from environment"""
    # Try to get from config file first
    try:
        config_path = os.path.join(os.path.dirname(__file__), 'config.php')
        with open(config_path, 'r') as f:
            config_content = f.read()
            # Extract API key and org ID using pattern match
            import re
            api_match = re.search(r"define\('OPENAI_API_KEY',\s*'([^']+)'\)", config_content)
            org_match = re.search(r"define\('OPENAI_ORG_ID',\s*'([^']+)'\)", config_content)
            
            if api_match:
                api_key = api_match.group(1)
                openai.api_key = api_key
                
                # Set organization ID if found
                if org_match:
                    org_id = org_match.group(1)
                    openai.organization = org_id
                    logging.info(f"Using OpenAI Organization ID: {org_id}")
                
                return True
    except Exception as e:
        logging.error(f"Error reading config file: {e}")
    
    # Fallback to environment variable
    api_key = os.environ.get('OPENAI_API_KEY')
    org_id = os.environ.get('OPENAI_ORG_ID')
    
    if api_key:
        openai.api_key = api_key
        if org_id:
            openai.organization = org_id
            logging.info(f"Using OpenAI Organization ID from env: {org_id}")
        return True
    
    logging.error("No API key found")
    return False

def send_chat_request(messages, model="gpt-4o-mini"):
    """Send a request to OpenAI chat API with cache metrics tracking"""
    try:
        logging.info(f"Sending chat request with {len(messages)} messages")
        response = openai.chat.completions.create(
            model=model,
            messages=messages,
            temperature=0.7,
            max_tokens=1000
        )
        
        # Extract cache metrics using utility function
        cache_metrics = extract_cache_metrics(response)
        
        # Log cache performance
        log_cache_performance(cache_metrics, context=f"model={model}")
        
        return {
            "success": True,
            "response": response.choices[0].message.content,
            "usage": cache_metrics,
            "model": model
        }
    except Exception as e:
        logging.error(f"Error in OpenAI API request: {e}")
        return {
            "success": False,
            "error": str(e)
        }

def process_chat(message_file, context_file, output_file, model="gpt-4o-mini"):
    """Process chat request using provided message and context"""
    try:
        # Read user message
        with open(message_file, 'r') as f:
            message = f.read().strip()
        
        # Read context (previous messages and transcription)
        with open(context_file, 'r') as f:
            context_data = json.load(f)
            messages = context_data.get('messages', [])
            transcription = context_data.get('transcription', '')
        
        logging.info(f"Processing chat: message={message[:30]}..., context size={len(messages)}")
        
        # Send request to OpenAI
        result = send_chat_request(messages, model)
        
        # Write result to output file
        with open(output_file, 'w') as f:
            json.dump(result, f)
        
        return result
    except Exception as e:
        logging.error(f"Error processing chat: {e}")
        result = {
            "success": False,
            "error": str(e)
        }
        with open(output_file, 'w') as f:
            json.dump(result, f)
        return result

def process_summarization(context_file, output_file, model="gpt-4o-mini"):
    """Summarize conversation messages"""
    try:
        # Read context (messages to summarize)
        with open(context_file, 'r') as f:
            context_data = json.load(f)
            messages = context_data.get('messages', [])
        
        logging.info(f"Processing summarization: context size={len(messages)}")
        
        # Send request to OpenAI
        result = send_chat_request(messages, model)
        
        # Write result to output file
        with open(output_file, 'w') as f:
            json.dump(result, f)
        
        return result
    except Exception as e:
        logging.error(f"Error processing summarization: {e}")
        result = {
            "success": False,
            "error": str(e)
        }
        with open(output_file, 'w') as f:
            json.dump(result, f)
        return result

def main():
    """Main function to parse arguments and process requests"""
    parser = argparse.ArgumentParser(description='Chat API for Intelligent Transcription')
    parser.add_argument('--message', type=str, help='Path to message file')
    parser.add_argument('--context', type=str, required=True, help='Path to context file')
    parser.add_argument('--output', type=str, required=True, help='Path to output file')
    parser.add_argument('--model', type=str, default="gpt-4o-mini", help='OpenAI model to use')
    parser.add_argument('--summarize', type=str, default="false", help='Set to "true" for summarization mode')
    
    args = parser.parse_args()
    
    # Setup API key
    if not setup_api_key():
        result = {
            "success": False,
            "error": "No API key available"
        }
        with open(args.output, 'w') as f:
            json.dump(result, f)
        return
    
    # Process based on mode
    if args.summarize.lower() == "true":
        result = process_summarization(args.context, args.output, args.model)
    else:
        # Validate required args for chat
        if not args.message:
            result = {
                "success": False,
                "error": "Message file is required for chat mode"
            }
            with open(args.output, 'w') as f:
                json.dump(result, f)
            return
            
        result = process_chat(args.message, args.context, args.output, args.model)
    
    # Log completion and status
    if result.get("success"):
        logging.info("Request completed successfully")
    else:
        logging.error(f"Request failed: {result.get('error')}")

if __name__ == "__main__":
    main()