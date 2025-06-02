/**
 * Tâches Cypress pour les tests de traduction
 * Simulent les opérations backend nécessaires pour les tests E2E
 */

import * as fs from 'fs'
import * as path from 'path'

// Simulation d'une base de données SQLite en mémoire pour les tests
interface TestDatabase {
  users: any[]
  transcriptions: any[]
  translations: any[]
  translation_cache: any[]
}

let testDb: TestDatabase = {
  users: [],
  transcriptions: [],
  translations: [],
  translation_cache: []
}

// Chemins des fichiers de test
const TEST_DB_PATH = path.join(__dirname, '..', 'temp', 'test.db')
const FIXTURES_PATH = path.join(__dirname, '..', 'fixtures')

export const translationTasks = {
  // Réinitialiser la base de données de test
  'db:resetTranslations': () => {
    testDb = {
      users: [],
      transcriptions: [],
      translations: [],
      translation_cache: []
    }
    return null
  },

  // Créer un utilisateur de test
  'db:createUser': (userData: any) => {
    const existingUser = testDb.users.find(u => u.email === userData.email)
    if (existingUser) {
      return existingUser.id
    }

    const user = {
      id: `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      email: userData.email,
      password: userData.password, // En production, ceci serait hashé
      name: userData.name || 'Test User',
      role: userData.type || 'user',
      created_at: new Date().toISOString()
    }

    testDb.users.push(user)
    return user.id
  },

  // Créer une transcription de test
  'db:createTranscription': (transcriptionData: any) => {
    const transcription = {
      id: transcriptionData.id || `trans_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      title: transcriptionData.title,
      language: transcriptionData.language,
      file_name: transcriptionData.file_name,
      status: transcriptionData.status || 'completed',
      duration: transcriptionData.duration,
      segments: transcriptionData.segments,
      user_id: transcriptionData.user_id || 'test_user_1',
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }

    testDb.transcriptions.push(transcription)
    return transcription.id
  },

  // Créer une traduction de test
  'db:createTranslation': (translationData: any) => {
    const translation = {
      id: translationData.id || `translation_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      transcription_id: translationData.transcription_id,
      user_id: translationData.user_id || 'test_user_1',
      target_language: translationData.target_language,
      source_language: translationData.source_language || 'en',
      provider_used: translationData.provider_used || 'gpt-4o-mini',
      status: translationData.status || 'pending',
      quality_score: translationData.quality_score,
      processing_time_seconds: translationData.processing_time,
      estimated_cost: translationData.estimated_cost || 0.008,
      actual_cost: translationData.actual_cost,
      segments_count: translationData.segments_count,
      total_duration_seconds: translationData.total_duration,
      word_count: translationData.word_count,
      character_count: translationData.character_count,
      has_word_timestamps: translationData.has_word_timestamps ?? true,
      has_emotional_context: translationData.has_emotional_context ?? false,
      has_character_names: translationData.has_character_names ?? false,
      has_technical_terms: translationData.has_technical_terms ?? false,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      started_at: translationData.started_at,
      completed_at: translationData.completed_at
    }

    testDb.translations.push(translation)
    return translation.id
  },

  // Compléter une traduction (simulation)
  'translation:complete': (data: any) => {
    const { id, quality_score, segments } = data
    
    const translation = testDb.translations.find(t => t.id === id)
    if (!translation) {
      throw new Error(`Translation ${id} not found`)
    }

    // Mettre à jour le statut et les données
    translation.status = 'completed'
    translation.quality_score = quality_score
    translation.completed_at = new Date().toISOString()
    translation.processing_time_seconds = Math.random() * 5 + 1 // 1-6 secondes
    translation.actual_cost = translation.estimated_cost * (0.9 + Math.random() * 0.2) // Variation ±10%

    // Sauvegarder les segments traduits
    translation.translated_segments = segments

    // Calculer les métriques de qualité
    translation.timestamp_preservation_score = 0.95 + Math.random() * 0.05
    translation.length_adaptation_ratio = 1.0 + (Math.random() - 0.5) * 0.2 // ±10%
    translation.emotional_preservation_score = quality_score * (0.9 + Math.random() * 0.1)

    return translation
  },

  // Obtenir les traductions pour un utilisateur
  'db:getTranslations': (filters: any = {}) => {
    let translations = testDb.translations

    if (filters.user_id) {
      translations = translations.filter(t => t.user_id === filters.user_id)
    }

    if (filters.status) {
      translations = translations.filter(t => t.status === filters.status)
    }

    if (filters.target_language) {
      translations = translations.filter(t => t.target_language === filters.target_language)
    }

    // Tri
    if (filters.sort_by) {
      translations.sort((a, b) => {
        const aVal = a[filters.sort_by]
        const bVal = b[filters.sort_by]
        
        if (filters.sort_order === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })
    }

    // Pagination
    const page = filters.page || 1
    const limit = filters.limit || 10
    const offset = (page - 1) * limit
    
    return {
      translations: translations.slice(offset, offset + limit),
      pagination: {
        current_page: page,
        per_page: limit,
        total_items: translations.length,
        total_pages: Math.ceil(translations.length / limit),
        has_next_page: page < Math.ceil(translations.length / limit),
        has_previous_page: page > 1
      }
    }
  },

  // Simuler la création d'une traduction via API
  'api:createTranslation': (requestData: any) => {
    const translationId = `api_translation_${Date.now()}`
    
    // Valider les données
    if (!requestData.transcription_id || !requestData.target_language) {
      throw new Error('Missing required fields')
    }

    // Vérifier que la transcription existe
    const transcription = testDb.transcriptions.find(t => t.id === requestData.transcription_id)
    if (!transcription) {
      throw new Error('Transcription not found')
    }

    // Estimer le coût et le temps
    const estimatedCost = transcription.duration * 0.008 // $0.008 par minute
    const estimatedTime = transcription.segments.length * 0.5 // 0.5s par segment

    // Créer la traduction
    const translation = {
      id: translationId,
      transcription_id: requestData.transcription_id,
      target_language: requestData.target_language,
      provider_used: requestData.provider || 'gpt-4o-mini',
      status: 'pending',
      estimated_cost: estimatedCost,
      estimated_processing_time: estimatedTime,
      created_at: new Date().toISOString()
    }

    testDb.translations.push({
      ...translation,
      user_id: 'test_user_1',
      source_language: transcription.language,
      segments_count: transcription.segments.length,
      total_duration_seconds: transcription.duration,
      has_word_timestamps: true,
      has_emotional_context: requestData.config?.preserve_emotions || false,
      has_character_names: requestData.config?.use_character_names || false,
      has_technical_terms: requestData.config?.technical_terms_handling || false
    })

    return {
      success: true,
      data: translation
    }
  },

  // Obtenir le statut d'une traduction
  'api:getTranslationStatus': (translationId: string) => {
    const translation = testDb.translations.find(t => t.id === translationId)
    if (!translation) {
      throw new Error('Translation not found')
    }

    return {
      success: true,
      data: translation
    }
  },

  // Simuler le téléchargement d'une traduction
  'translation:download': (translationId: string, format: string) => {
    const translation = testDb.translations.find(t => t.id === translationId)
    if (!translation || translation.status !== 'completed') {
      throw new Error('Translation not completed or not found')
    }

    // Données simulées selon le format
    const downloadData = {
      json: {
        translation_id: translationId,
        target_language: translation.target_language,
        segments: translation.translated_segments || [],
        metadata: {
          quality_score: translation.quality_score,
          timestamp_preservation_score: translation.timestamp_preservation_score,
          provider_used: translation.provider_used
        }
      },
      srt: generateSRTContent(translation.translated_segments || []),
      vtt: generateVTTContent(translation.translated_segments || []),
      txt: (translation.translated_segments || []).map((s: any) => s.text).join('\n'),
      dubbing_json: {
        segments: translation.translated_segments || [],
        dubbing_metadata: {
          length_adaptation_ratio: translation.length_adaptation_ratio,
          emotional_preservation_score: translation.emotional_preservation_score,
          timing_precision: translation.timestamp_preservation_score
        }
      }
    }

    return downloadData[format as keyof typeof downloadData] || downloadData.json
  },

  // Charger les données de test depuis les fixtures
  'fixtures:loadTranslationData': () => {
    const fixturesFile = path.join(FIXTURES_PATH, 'translation-test-data.json')
    if (fs.existsSync(fixturesFile)) {
      const data = JSON.parse(fs.readFileSync(fixturesFile, 'utf8'))
      return data
    }
    return null
  },

  // Seed des données de test
  'db:seedTestData': () => {
    const fixtures = translationTasks['fixtures:loadTranslationData']()
    if (!fixtures) return

    // Ajouter les utilisateurs de test
    fixtures.testUsers.forEach((user: any) => {
      translationTasks['db:createUser'](user)
    })

    // Ajouter les transcriptions de test
    fixtures.testTranscriptions.forEach((transcription: any) => {
      translationTasks['db:createTranscription'](transcription)
    })

    return 'Test data seeded successfully'
  },

  // Nettoyer les fichiers temporaires
  'cleanup:tempFiles': () => {
    const tempDir = path.join(__dirname, '..', 'temp')
    if (fs.existsSync(tempDir)) {
      fs.rmSync(tempDir, { recursive: true, force: true })
    }
    fs.mkdirSync(tempDir, { recursive: true })
    return 'Temp files cleaned'
  }
}

// Fonctions utilitaires

function generateSRTContent(segments: any[]): string {
  return segments.map((segment, index) => {
    const startTime = formatSRTTime(segment.start)
    const endTime = formatSRTTime(segment.end)
    return `${index + 1}\n${startTime} --> ${endTime}\n${segment.text}\n`
  }).join('\n')
}

function generateVTTContent(segments: any[]): string {
  const header = 'WEBVTT\n\n'
  const content = segments.map((segment) => {
    const startTime = formatVTTTime(segment.start)
    const endTime = formatVTTTime(segment.end)
    return `${startTime} --> ${endTime}\n${segment.text}\n`
  }).join('\n')
  return header + content
}

function formatSRTTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 1000)
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')},${ms.toString().padStart(3, '0')}`
}

function formatVTTTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 1000)
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}.${ms.toString().padStart(3, '0')}`
}

export default translationTasks