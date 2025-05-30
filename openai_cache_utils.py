#!/usr/bin/env python3
"""
OpenAI Cache Utilities
Provides utilities for tracking and optimizing OpenAI prompt caching
"""

import json
import logging
from typing import Dict, Any, Optional

def extract_cache_metrics(response) -> Dict[str, Any]:
    """
    Extract cache metrics from OpenAI API response
    
    Args:
        response: OpenAI API response object
        
    Returns:
        Dictionary containing cache metrics
    """
    try:
        # Convert response to dict if needed
        if hasattr(response, 'model_dump'):
            response_dict = response.model_dump()
        elif hasattr(response, 'to_dict'):
            response_dict = response.to_dict()
        else:
            response_dict = dict(response)
        
        # Extract usage information
        usage = response_dict.get('usage', {})
        prompt_tokens = usage.get('prompt_tokens', 0)
        completion_tokens = usage.get('completion_tokens', 0)
        total_tokens = usage.get('total_tokens', 0)
        
        # Extract cache details
        prompt_details = usage.get('prompt_tokens_details', {})
        cached_tokens = prompt_details.get('cached_tokens', 0)
        
        # Calculate metrics
        cache_hit_rate = 0
        if prompt_tokens > 0:
            cache_hit_rate = round((cached_tokens / prompt_tokens) * 100, 2)
        
        # Estimate cost savings (approximate)
        # GPT-4o-mini: $0.15/1M input tokens, cache provides ~50% discount
        tokens_in_millions = cached_tokens / 1_000_000
        cost_per_million = 0.15  # USD for gpt-4o-mini
        cache_discount = 0.5
        estimated_savings = tokens_in_millions * cost_per_million * cache_discount
        
        return {
            'prompt_tokens': prompt_tokens,
            'completion_tokens': completion_tokens,
            'total_tokens': total_tokens,
            'cached_tokens': cached_tokens,
            'cache_hit_rate': cache_hit_rate,
            'cache_eligible': prompt_tokens >= 1024,
            'estimated_cost_saved_usd': round(estimated_savings, 6),
            'cache_metrics_available': cached_tokens > 0 or 'prompt_tokens_details' in usage
        }
    except Exception as e:
        logging.error(f"Error extracting cache metrics: {e}")
        return {
            'prompt_tokens': 0,
            'completion_tokens': 0,
            'total_tokens': 0,
            'cached_tokens': 0,
            'cache_hit_rate': 0,
            'cache_eligible': False,
            'estimated_cost_saved_usd': 0,
            'cache_metrics_available': False,
            'error': str(e)
        }

def log_cache_performance(metrics: Dict[str, Any], context: str = "") -> None:
    """
    Log cache performance metrics
    
    Args:
        metrics: Cache metrics dictionary
        context: Optional context string for the log
    """
    if metrics.get('cache_metrics_available'):
        log_msg = f"OpenAI Cache Performance"
        if context:
            log_msg += f" [{context}]"
        log_msg += f": {metrics['cached_tokens']}/{metrics['prompt_tokens']} tokens cached"
        log_msg += f" ({metrics['cache_hit_rate']}% hit rate)"
        log_msg += f", saved ~${metrics['estimated_cost_saved_usd']:.4f}"
        logging.info(log_msg)
    else:
        logging.debug(f"Cache metrics not available for this request{' [' + context + ']' if context else ''}")

def format_cache_report(metrics: Dict[str, Any]) -> str:
    """
    Format cache metrics into a human-readable report
    
    Args:
        metrics: Cache metrics dictionary
        
    Returns:
        Formatted string report
    """
    report = "=== OpenAI Prompt Cache Report ===\n"
    report += f"Prompt Tokens: {metrics['prompt_tokens']:,}\n"
    report += f"Cached Tokens: {metrics['cached_tokens']:,}\n"
    report += f"Cache Hit Rate: {metrics['cache_hit_rate']}%\n"
    report += f"Cache Eligible: {'Yes' if metrics['cache_eligible'] else 'No (< 1024 tokens)'}\n"
    report += f"Est. Cost Saved: ${metrics['estimated_cost_saved_usd']:.4f}\n"
    
    if not metrics['cache_metrics_available']:
        report += "\nNote: Cache metrics not available (model may not support caching)\n"
    
    return report

def save_cache_metrics_to_file(metrics: Dict[str, Any], filename: str = "cache_metrics.json") -> None:
    """
    Save cache metrics to a JSON file for analysis
    
    Args:
        metrics: Cache metrics dictionary
        filename: Output filename
    """
    try:
        # Load existing metrics if file exists
        try:
            with open(filename, 'r') as f:
                all_metrics = json.load(f)
        except FileNotFoundError:
            all_metrics = []
        
        # Add timestamp to current metrics
        import datetime
        metrics['timestamp'] = datetime.datetime.now().isoformat()
        
        # Append new metrics
        all_metrics.append(metrics)
        
        # Save updated metrics
        with open(filename, 'w') as f:
            json.dump(all_metrics, f, indent=2)
            
        logging.info(f"Cache metrics saved to {filename}")
    except Exception as e:
        logging.error(f"Error saving cache metrics: {e}")