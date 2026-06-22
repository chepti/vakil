<template>
  <AppLayout :title="person.full_name">
    <div class="show-page" dir="rtl">

      <!-- ─── Hero ─── -->
      <div class="person-hero" :class="[person.gender, { deceased: person.is_deceased }]">
        <div class="hero-content">

          <!-- Avatar with photo-change overlay -->
          <div class="avatar-wrap" @click="showChangePhoto = true" title="שנה תמונה">
            <img v-if="photoPreview || person.photo_url" :src="photoPreview || person.photo_url" :alt="person.full_name" />
            <div v-else class="initials-large">{{ initials(person.full_name) }}</div>
            <div class="avatar-overlay">📷</div>
          </div>

          <div class="hero-info">
            <div class="name-row">
              <h1>{{ person.full_name }}</h1>
              <span v-if="person.is_deceased" class="deceased-badge">ז"ל</span>
            </div>
            <div class="meta-chips">
              <span v-if="person.birth_date_gregorian" class="chip">
                🎂 {{ formatDate(person.birth_date_gregorian) }}
                <span v-if="person.birth_date_hebrew" class="hebrew-date"> / {{ person.birth_date_hebrew }}</span>
              </span>
              <span v-if="person.is_deceased && person.death_date_gregorian" class="chip chip-gray">
                🕯 {{ formatDate(person.death_date_gregorian) }}
                <span v-if="person.death_date_hebrew"> / {{ person.death_date_hebrew }}</span>
              </span>
              <span v-if="person.city" class="chip">📍 {{ person.city }}</span>
              <span v-if="person.current_occupation" class="chip">💼 {{ person.current_occupation }}</span>
              <span v-if="person.maiden_name" class="chip">👰 שם נעורים: {{ person.maiden_name }}</span>
              <span v-if="person.email" class="chip" dir="ltr">✉️ {{ person.email }}</span>
              <span v-if="person.phone" class="chip" dir="ltr">📞 {{ person.phone }}</span>
              <button
                v-if="person.email"
                class="chip chip-invite"
                :disabled="resendingInvite"
                @click="resendInvite"
              >
                {{ resendingInvite ? 'שולח...' : '📨 שלח הזמנה שוב' }}
              </button>
            </div>
            <p v-if="person.bio" class="bio-text">{{ person.bio }}</p>
          </div>

          <div class="hero-actions">
            <button class="btn-edit" @click="openEditPersonal">✏️ עריכה</button>
            <button v-if="$page.props.auth.user.role === 'admin'" class="btn-delete" @click="showDeleteConfirm = true">🗑 מחיקה</button>
          </div>
        </div>
      </div>

      <!-- ─── Family grid ─── -->
      <div class="family-grid">

        <!-- הורים -->
        <div class="family-section">
          <div class="section-header">
            <h2>הורים {{ parents.length ? `(${parents.length})` : '' }}</h2>
            <button v-if="parents.length < 2" class="btn-add-inline" @click="openAddParent">+ הוסף הורה</button>
          </div>
          <div class="family-cards">
            <Link v-for="p in parents" :key="p.id" :href="`/people/${p.id}`" class="mini-card" :class="p.gender">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
            <div v-if="parents.length === 0" class="empty-family">לא הוגדרו הורים</div>
          </div>
        </div>

        <!-- בן/בת זוג -->
        <div class="family-section">
          <div class="section-header">
            <h2>בן/בת זוג</h2>
            <button class="btn-add-inline" @click="showAddSpouse = true">+ הוסף/י</button>
          </div>
          <div class="family-cards">
            <Link v-for="p in spouses" :key="p.id" :href="`/people/${p.id}`" class="mini-card">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
              <span v-if="p.marriage_date_gregorian || p.marriage_date_hebrew" class="mini-marriage">
                💍 {{ p.marriage_date_gregorian ? formatDate(p.marriage_date_gregorian) : '' }}{{ p.marriage_date_hebrew ? ' / ' + p.marriage_date_hebrew : '' }}
              </span>
            </Link>
            <div v-if="spouses.length === 0" class="empty-family">לא הוגדר</div>
          </div>
        </div>

        <!-- אחים -->
        <div class="family-section" v-if="siblings.length > 0 || parents.length > 0">
          <div class="section-header">
            <h2>אחים ואחיות {{ siblings.length ? `(${siblings.length})` : '' }}</h2>
            <button v-if="parents.length > 0" class="btn-add-inline" @click="openAddSibling">+ הוסף אח/אחות</button>
          </div>
          <div class="family-cards">
            <Link v-for="p in siblings" :key="p.id" :href="`/people/${p.id}`" class="mini-card" :class="p.gender">
              <div class="mini-avatar">
                <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
              </div>
              <span>{{ p.full_name }}</span>
            </Link>
            <div v-if="siblings.length === 0" class="empty-family">אין אחים/אחיות רשומים</div>
          </div>
        </div>

        <!-- ילדים -->
        <div class="family-section">
          <div class="section-header">
            <h2>ילדים {{ children.length ? `(${children.length})` : '' }}</h2>
            <div class="section-header-actions">
              <button v-if="children.length > 1 && !reorderingChildren" class="btn-add-inline btn-reorder" @click="startReorderChildren">↕ סדר</button>
              <button v-if="reorderingChildren" class="btn-add-inline btn-reorder-save" @click="saveChildrenOrder">✓ שמור סדר</button>
              <button v-if="reorderingChildren" class="btn-add-inline btn-reorder-cancel" @click="cancelReorderChildren">✕</button>
              <button class="btn-add-inline" @click="openAddChild">+ הוסף ילד/ה</button>
            </div>
          </div>
          <div class="family-cards" :class="{ 'reordering': reorderingChildren }">
            <template v-if="reorderingChildren">
              <div
                v-for="(p, idx) in childrenOrder"
                :key="p.id"
                class="mini-card draggable"
                :class="[p.gender, { 'drag-over': dragOverIdx === idx, 'dragging': dragIdx === idx }]"
                draggable="true"
                @dragstart="onDragStart(idx)"
                @dragover.prevent="onDragOver(idx)"
                @drop="onDrop(idx)"
                @dragend="onDragEnd"
              >
                <div class="drag-handle">⠿</div>
                <div class="mini-avatar">
                  <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                  <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
                </div>
                <span>{{ p.full_name }}</span>
              </div>
            </template>
            <template v-else>
              <div v-for="p in children" :key="p.id" class="mini-card-wrap">
                <Link :href="`/people/${p.id}`" class="mini-card" :class="p.gender">
                  <div class="mini-avatar">
                    <img v-if="p.photo_url" :src="p.photo_url" :alt="p.full_name" />
                    <div v-else class="mini-initials">{{ initials(p.full_name) }}</div>
                  </div>
                  <span>{{ p.full_name }}</span>
                </Link>
                <button
                  v-if="isAdmin && spouses.length"
                  class="btn-coparent"
                  @click.prevent.stop="openChildParent(p)"
                  title="הגדר מי ההורה השני של הילד/ה"
                >👪</button>
              </div>
            </template>
            <div v-if="children.length === 0" class="empty-family">אין ילדים רשומים</div>
          </div>
        </div>

      </div>

      <!-- ─── Photos tagged ─── -->
      <div class="family-section photos-section" v-if="photosTagged.length > 0">
        <div class="section-header">
          <h2>תמונות משפחתיות ({{ photosTagged.length }})</h2>
          <Link href="/family-photos" class="btn-add-inline">+ הוסף תמונה</Link>
        </div>
        <p class="photos-hint">לחץ/י על "הגדר כפרופיל" כדי לבחור תמונת פנים מהתמונה</p>
        <div class="photos-grid">
          <div v-for="pt in photosTagged" :key="pt.tag_id" class="photo-thumb-wrap">
            <Link :href="`/family-photos/${pt.id}`" class="photo-thumb-link">
              <img :src="pt.url" :alt="pt.title || ''" />
              <span v-if="pt.title" class="photo-caption">{{ pt.title }}</span>
            </Link>
            <button
              class="btn-set-profile"
              :class="{ loading: settingProfile === pt.tag_id }"
              @click.prevent="cropAndSetProfile(pt)"
              :disabled="!!settingProfile"
              title="הגדר כתמונת פרופיל"
            >
              {{ settingProfile === pt.tag_id ? '...' : '📷 הגדר' }}
            </button>
          </div>
        </div>
        <p v-if="profileSetMsg" class="profile-set-msg" :class="profileSetMsg.ok ? 'ok' : 'err'">
          {{ profileSetMsg.text }}
        </p>
      </div>
      <div class="photos-empty-action" v-else>
        <Link href="/family-photos" class="btn-add-inline">📸 הוסף תמונות משפחתיות</Link>
      </div>

    </div>

    <!-- ─── Modal: שינוי תמונה ─── -->
    <div v-if="showChangePhoto" class="modal-overlay" @click.self="closePhotoModal">
      <div class="modal" dir="rtl">
        <h3>שינוי תמונת פרופיל</h3>
        <div class="photo-modal-row">
          <div class="photo-preview-wrap">
            <img v-if="photoPreview || person.photo_url" :src="photoPreview || person.photo_url" class="photo-preview" />
            <div v-else class="photo-placeholder">{{ initials(person.full_name) }}</div>
          </div>
          <div class="photo-controls">
            <label class="btn-choose-photo">
              📷 בחר תמונה
              <input type="file" accept="image/jpeg,image/png,image/webp" @change="handlePhotoSelect" hidden />
            </label>
            <p class="photo-hint">JPG / PNG / WebP עד 5MB</p>
          </div>
        </div>
        <p v-if="photoForm.errors.profile_photo" class="error-msg">{{ photoForm.errors.profile_photo }}</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="closePhotoModal">ביטול</button>
          <button v-if="photoForm.profile_photo" class="btn-primary-modal" @click="submitPhoto" :disabled="photoForm.processing">
            {{ photoForm.processing ? 'מעלה...' : 'העלה' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: עריכת פרטים אישיים ─── -->
    <div v-if="showEditPersonal" class="modal-overlay" @click.self="showEditPersonal = false">
      <div class="modal modal-wide" dir="rtl">
        <h3>✏️ עריכת פרטי {{ person.full_name }}</h3>

        <div class="form-row">
          <div class="form-group">
            <label>שם פרטי *</label>
            <input v-model="editForm.first_name" type="text" :class="{ 'is-error': editForm.errors.first_name }" />
            <span class="error-msg" v-if="editForm.errors.first_name">{{ editForm.errors.first_name }}</span>
          </div>
          <div class="form-group">
            <label>שם משפחה *</label>
            <input v-model="editForm.last_name" type="text" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>מגדר *</label>
            <div class="gender-toggle">
              <button type="button" :class="{ active: editForm.gender === 'male' }" @click="editForm.gender = 'male'">זכר</button>
              <button type="button" :class="{ active: editForm.gender === 'female' }" @click="editForm.gender = 'female'">נקבה</button>
            </div>
          </div>
          <div class="form-group">
            <label>תאריך לידה (לועזי)</label>
            <input v-model="editForm.birth_date_gregorian" type="date" @change="syncH(editForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
          <div class="form-group">
            <label>תאריך לידה עברי</label>
            <input v-model="editForm.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(editForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>{{ editForm.is_deceased ? 'עיסוק' : 'מה עושה כיום' }}</label>
            <input v-model="editForm.current_occupation" type="text" />
          </div>
          <div class="form-group">
            <label>עיר מגורים</label>
            <input v-model="editForm.city" type="text" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>אימייל</label>
            <input v-model="editForm.email" type="email" dir="ltr" />
          </div>
          <div class="form-group">
            <label>טלפון</label>
            <input v-model="editForm.phone" type="tel" dir="ltr" />
          </div>
        </div>

        <div class="form-group">
          <label>ביוגרפיה</label>
          <textarea v-model="editForm.bio" rows="3"></textarea>
        </div>

        <div class="deceased-section">
          <label class="checkbox-label">
            <input type="checkbox" v-model="editForm.is_deceased" />
            <span>נפטר/ה</span>
          </label>
          <div v-if="editForm.is_deceased" class="form-row" style="margin-top: 0.75rem">
            <div class="form-group">
              <label>תאריך פטירה (לועזי)</label>
              <input v-model="editForm.death_date_gregorian" type="date" @change="syncH(editForm,'death_date_gregorian','death_date_hebrew')" />
            </div>
            <div class="form-group">
              <label>תאריך פטירה עברי</label>
              <input v-model="editForm.death_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(editForm,'death_date_gregorian','death_date_hebrew')" />
            </div>
          </div>
        </div>

        <div class="modal-actions" style="margin-top: 1.5rem">
          <button class="btn-cancel" @click="showEditPersonal = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitEdit" :disabled="editForm.processing">
            {{ editForm.processing ? 'שומר...' : 'שמור שינויים' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הוספת הורה ─── -->
    <div v-if="showAddParent" class="modal-overlay" @click.self="showAddParent = false">
      <div class="modal" dir="rtl">
        <h3>הוספת הורה ל{{ person.full_name }}</h3>
        <div class="tab-bar">
          <button :class="['tab', { active: parentTab === 'new' }]" @click="parentTab = 'new'">דמות חדשה</button>
          <button :class="['tab', { active: parentTab === 'existing' }]" @click="parentTab = 'existing'">מהעץ הקיים</button>
        </div>

        <div v-if="parentTab === 'new'">
          <div class="form-row">
            <div class="form-group">
              <label>שם פרטי *</label>
              <input v-model="parentForm.first_name" type="text" autofocus />
            </div>
            <div class="form-group">
              <label>שם משפחה</label>
              <input v-model="parentForm.last_name" type="text" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>מגדר *</label>
              <div class="gender-toggle">
                <button type="button" :class="{ active: parentForm.gender === 'male' }" @click="parentForm.gender = 'male'">זכר</button>
                <button type="button" :class="{ active: parentForm.gender === 'female' }" @click="parentForm.gender = 'female'">נקבה</button>
              </div>
            </div>
            <div class="form-group">
              <label>תאריך לידה (לועזי)</label>
              <input v-model="parentForm.birth_date_gregorian" type="date" @change="syncH(parentForm,'birth_date_gregorian','birth_date_hebrew')" />
            </div>
            <div class="form-group">
              <label>תאריך לידה עברי</label>
              <input v-model="parentForm.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(parentForm,'birth_date_gregorian','birth_date_hebrew')" />
            </div>
          </div>
        </div>

        <div v-if="parentTab === 'existing'">
          <div class="form-group">
            <label>חיפוש בעץ</label>
            <input v-model="parentSearch" type="text" placeholder="הקלד שם..." />
          </div>
          <div class="existing-list">
            <button
              v-for="p in filteredForParent"
              :key="p.id"
              :class="['existing-item', { selected: parentForm.existing_id === p.id }]"
              @click="parentForm.existing_id = p.id"
            >
              <div class="mini-initials-sm">{{ initials(p.label) }}</div>
              {{ p.label }}
            </button>
            <div v-if="filteredForParent.length === 0" class="empty-family">לא נמצאו תוצאות</div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddParent = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitParent"
            :disabled="!canSubmitParent || savingParent">
            {{ savingParent ? 'שומר...' : 'הוסף הורה' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הוספת בן/בת זוג ─── -->
    <div v-if="showAddSpouse" class="modal-overlay" @click.self="showAddSpouse = false">
      <div class="modal" dir="rtl">
        <h3>הוספת בן/בת זוג ל{{ person.full_name }}</h3>
        <div class="tab-bar">
          <button :class="['tab', { active: spouseTab === 'new' }]" @click="spouseTab = 'new'">דמות חדשה</button>
          <button :class="['tab', { active: spouseTab === 'existing' }]" @click="spouseTab = 'existing'">מהעץ הקיים</button>
        </div>

        <div v-if="spouseTab === 'new'">
          <div class="form-row">
            <div class="form-group">
              <label>שם פרטי *</label>
              <input v-model="spouseForm.first_name" type="text" autofocus />
            </div>
            <div class="form-group">
              <label>שם משפחה</label>
              <input v-model="spouseForm.last_name" type="text" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>מגדר *</label>
              <div class="gender-toggle">
                <button type="button" :class="{ active: spouseForm.gender === 'male' }" @click="spouseForm.gender = 'male'">זכר</button>
                <button type="button" :class="{ active: spouseForm.gender === 'female' }" @click="spouseForm.gender = 'female'">נקבה</button>
              </div>
            </div>
            <div class="form-group">
              <label>תאריך לידה (לועזי)</label>
              <input v-model="spouseForm.birth_date_gregorian" type="date" @change="syncH(spouseForm,'birth_date_gregorian','birth_date_hebrew')" />
            </div>
            <div class="form-group">
              <label>תאריך לידה עברי</label>
              <input v-model="spouseForm.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(spouseForm,'birth_date_gregorian','birth_date_hebrew')" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>תאריך נישואין (לועזי)</label>
              <input v-model="spouseForm.marriage_date_gregorian" type="date" @change="syncH(spouseForm,'marriage_date_gregorian','marriage_date_hebrew')" />
            </div>
            <div class="form-group">
              <label>תאריך נישואין עברי</label>
              <input v-model="spouseForm.marriage_date_hebrew" type="text" placeholder='כ"ב אייר תש"פ' @change="syncG(spouseForm,'marriage_date_gregorian','marriage_date_hebrew')" />
            </div>
          </div>
        </div>

        <div v-if="spouseTab === 'existing'">
          <div class="form-group">
            <label>חיפוש בעץ</label>
            <input v-model="spouseSearch" type="text" placeholder="הקלד שם..." />
          </div>
          <div class="existing-list">
            <button
              v-for="p in filteredForSpouse"
              :key="p.id"
              :class="['existing-item', { selected: spouseForm.existing_id === p.id }]"
              @click="spouseForm.existing_id = p.id"
            >
              <div class="mini-initials-sm">{{ initials(p.label) }}</div>
              {{ p.label }}
            </button>
            <div v-if="filteredForSpouse.length === 0" class="empty-family">לא נמצאו תוצאות</div>
          </div>
          <div class="form-row" style="margin-top: 0.75rem">
            <div class="form-group">
              <label>תאריך נישואין (לועזי)</label>
              <input v-model="spouseForm.marriage_date_gregorian" type="date" @change="syncH(spouseForm,'marriage_date_gregorian','marriage_date_hebrew')" />
            </div>
            <div class="form-group">
              <label>תאריך נישואין עברי</label>
              <input v-model="spouseForm.marriage_date_hebrew" type="text" placeholder='כ"ב אייר תש"פ' @change="syncG(spouseForm,'marriage_date_gregorian','marriage_date_hebrew')" />
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddSpouse = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitSpouse"
            :disabled="!canSubmitSpouse || savingSpouse">
            {{ savingSpouse ? 'שומר...' : 'הוסף/י' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הוספת אח/אחות ─── -->
    <div v-if="showAddSibling" class="modal-overlay" @click.self="showAddSibling = false">
      <div class="modal" dir="rtl">
        <h3>הוספת אח/אחות ל{{ person.full_name }}</h3>
        <p class="modal-info" v-if="parents.length">יקבל/ת את אותם הורים: {{ parents.map(p => p.full_name).join(', ') }}</p>

        <div class="form-row">
          <div class="form-group">
            <label>שם פרטי *</label>
            <input v-model="siblingForm.first_name" type="text" autofocus />
          </div>
          <div class="form-group">
            <label>שם משפחה</label>
            <input v-model="siblingForm.last_name" type="text" :placeholder="person.last_name" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>מגדר *</label>
            <div class="gender-toggle">
              <button type="button" :class="{ active: siblingForm.gender === 'male' }" @click="siblingForm.gender = 'male'">זכר</button>
              <button type="button" :class="{ active: siblingForm.gender === 'female' }" @click="siblingForm.gender = 'female'">נקבה</button>
            </div>
          </div>
          <div class="form-group">
            <label>תאריך לידה (לועזי)</label>
            <input v-model="siblingForm.birth_date_gregorian" type="date" @change="syncH(siblingForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
          <div class="form-group">
            <label>תאריך לידה עברי</label>
            <input v-model="siblingForm.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(siblingForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddSibling = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitSibling"
            :disabled="!siblingForm.first_name || !siblingForm.gender || savingSibling">
            {{ savingSibling ? 'שומר...' : 'הוסף אח/אחות' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הוספת ילד ─── -->
    <div v-if="showAddChild" class="modal-overlay" @click.self="showAddChild = false">
      <div class="modal" dir="rtl">
        <h3>הוספת ילד/ה ל{{ person.full_name }}</h3>
        <div class="form-row">
          <div class="form-group">
            <label>שם פרטי *</label>
            <input v-model="childForm.first_name" type="text" autofocus />
          </div>
          <div class="form-group">
            <label>שם משפחה</label>
            <input v-model="childForm.last_name" type="text" :placeholder="person.last_name" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>מגדר *</label>
            <div class="gender-toggle">
              <button type="button" :class="{ active: childForm.gender === 'male' }" @click="childForm.gender = 'male'">זכר</button>
              <button type="button" :class="{ active: childForm.gender === 'female' }" @click="childForm.gender = 'female'">נקבה</button>
            </div>
          </div>
          <div class="form-group">
            <label>תאריך לידה (לועזי)</label>
            <input v-model="childForm.birth_date_gregorian" type="date" @change="syncH(childForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
          <div class="form-group">
            <label>תאריך לידה עברי</label>
            <input v-model="childForm.birth_date_hebrew" type="text" placeholder='כ"ה תשרי תשפ"ה' @change="syncG(childForm,'birth_date_gregorian','birth_date_hebrew')" />
          </div>
        </div>
        <div class="form-group" v-if="spouses.length === 1">
          <label class="checkbox-label">
            <input type="checkbox" v-model="childForm.add_spouse_as_parent" />
            <span>הוסף גם את {{ spouses[0].full_name }} כהורה שני</span>
          </label>
        </div>
        <div class="form-group" v-else-if="spouses.length > 1">
          <label>ההורה השני (האם/האב)</label>
          <select v-model="childForm.co_parent_id" class="rel-select">
            <option :value="null">— ללא —</option>
            <option v-for="s in spouses" :key="s.id" :value="s.id">{{ s.full_name }}</option>
          </select>
          <p class="hint-small">בחירת הורה שני תשייך את הילד במפורש לזוג הזה — לא לבני-זוג אחרים.</p>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="showAddChild = false">ביטול</button>
          <button class="btn-primary-modal" @click="submitChild"
            :disabled="!childForm.first_name || !childForm.gender || savingChild">
            {{ savingChild ? 'שומר...' : 'הוסף ילד/ה' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: הורה שני של ילד/ה ─── -->
    <div v-if="showChildParent" class="modal-overlay" @click.self="showChildParent = false">
      <div class="modal" dir="rtl">
        <h3>ההורה השני של {{ childParentTarget?.full_name }}</h3>
        <p class="hint-small">בחר/י מי ההורה הנוסף (יחד עם {{ person.full_name }}). השיוך גובר על השיוך האוטומטי לכל בני-הזוג.</p>
        <div class="form-group">
          <label>הורה שני</label>
          <select v-model="childParentCoId" class="rel-select">
            <option :value="null">— ללא (רק {{ person.full_name }}) —</option>
            <option v-for="s in spouses" :key="s.id" :value="s.id">{{ s.full_name }}</option>
          </select>
        </div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showChildParent = false">ביטול</button>
          <button class="btn-primary-modal" @click="saveChildParent" :disabled="savingChildParent">
            {{ savingChildParent ? 'שומר...' : 'שמור' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: אישור מחיקה ─── -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal" dir="rtl">
        <h3>מחיקת דמות</h3>
        <p>האם למחוק את <strong>{{ person.full_name }}</strong>?</p>
        <p class="modal-warning">פעולה זו תמחק את כל הקשרים ואינה ניתנת לביטול.</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showDeleteConfirm = false">ביטול</button>
          <button class="btn-delete-confirm" @click="deletePerson" :disabled="deleting">
            {{ deleting ? 'מוחק...' : 'כן, מחק' }}
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { gregorianToHebrew, hebrewToGregorian } from '@/utils/hebrewDate'

// המרה דו-כיוונית על אובייקט טופס נתון — ממלא רק אם ההמרה הצליחה
function syncH(obj, gKey, hKey)  { const v = gregorianToHebrew(obj[gKey]); if (v) obj[hKey] = v }
function syncG(obj, gKey, hKey)  { const v = hebrewToGregorian(obj[hKey]); if (v) obj[gKey] = v }

const props = defineProps({
  person:       { type: Object, required: true },
  parents:      { type: Array,  default: () => [] },
  children:     { type: Array,  default: () => [] },
  spouses:      { type: Array,  default: () => [] },
  siblings:     { type: Array,  default: () => [] },
  photosTagged: { type: Array,  default: () => [] },
  allPeople:    { type: Array,  default: () => [] },
  parentIds:    { type: Array,  default: () => [] },
  spouseId:     { type: [Number, null], default: null },
})

// ─── Photo upload ─────────────────────────────────────────
const showChangePhoto = ref(false)
const photoPreview    = ref(null)
const photoForm = useForm({ profile_photo: null })

function handlePhotoSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  photoForm.profile_photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function closePhotoModal() {
  showChangePhoto.value = false
  if (!props.person.photo_url) photoPreview.value = null
}

function submitPhoto() {
  photoForm.post(`/people/${props.person.id}/photo`, {
    onSuccess: () => { showChangePhoto.value = false },
  })
}

// ─── Edit personal info ──────────────────────────────────
const showEditPersonal = ref(false)
const editForm = useForm({
  first_name:           props.person.first_name,
  last_name:            props.person.last_name,
  gender:               props.person.gender,
  birth_date_gregorian: props.person.birth_date_gregorian ?? '',
  birth_date_hebrew:    props.person.birth_date_hebrew ?? '',
  is_deceased:          props.person.is_deceased,
  death_date_gregorian: props.person.death_date_gregorian ?? '',
  death_date_hebrew:    props.person.death_date_hebrew ?? '',
  current_occupation:   props.person.current_occupation ?? '',
  bio:                  props.person.bio ?? '',
  city:                 props.person.city ?? '',
  email:                props.person.email ?? '',
  phone:                props.person.phone ?? '',
  parent_ids:           [...props.parentIds],
  spouse_id:            props.spouseId,
})

function openEditPersonal() {
  editForm.first_name           = props.person.first_name
  editForm.last_name            = props.person.last_name
  editForm.gender               = props.person.gender
  editForm.birth_date_gregorian = props.person.birth_date_gregorian ?? ''
  editForm.birth_date_hebrew    = props.person.birth_date_hebrew ?? ''
  editForm.is_deceased          = props.person.is_deceased
  editForm.death_date_gregorian = props.person.death_date_gregorian ?? ''
  editForm.death_date_hebrew    = props.person.death_date_hebrew ?? ''
  editForm.current_occupation   = props.person.current_occupation ?? ''
  editForm.bio                  = props.person.bio ?? ''
  editForm.city                 = props.person.city ?? ''
  editForm.email                = props.person.email ?? ''
  editForm.phone                = props.person.phone ?? ''
  showEditPersonal.value        = true
}

function submitEdit() {
  editForm.patch(`/people/${props.person.id}`, {
    onSuccess: () => { showEditPersonal.value = false },
  })
}

// ─── Resend invitation ───────────────────────────────────
const resendingInvite = ref(false)
function resendInvite() {
  resendingInvite.value = true
  router.post(`/people/${props.person.id}/resend-invite`, {}, {
    preserveScroll: true,
    onFinish: () => { resendingInvite.value = false },
  })
}

// ─── Add parent ──────────────────────────────────────────
const showAddParent = ref(false)
const parentTab     = ref('new')
const parentSearch  = ref('')
const savingParent  = ref(false)
const parentForm    = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', birth_date_hebrew: '', existing_id: null,
})

function openAddParent() {
  parentForm.first_name = ''; parentForm.last_name = ''; parentForm.gender = ''
  parentForm.birth_date_gregorian = ''; parentForm.birth_date_hebrew = ''; parentForm.existing_id = null
  parentTab.value = 'new'; parentSearch.value = ''
  showAddParent.value = true
}

const filteredForParent = computed(() => {
  const q = parentSearch.value.toLowerCase()
  const excludeIds = new Set([props.person.id, ...props.parents.map(p => p.id)])
  return props.allPeople
    .filter(p => !excludeIds.has(p.id))
    .filter(p => !q || p.label.toLowerCase().includes(q))
    .slice(0, 8)
})

const canSubmitParent = computed(() => {
  if (parentTab.value === 'new') return parentForm.first_name && parentForm.gender
  return !!parentForm.existing_id
})

function submitParent() {
  savingParent.value = true
  const payload = parentTab.value === 'existing'
    ? { existing_id: parentForm.existing_id }
    : {
        first_name:           parentForm.first_name,
        last_name:            parentForm.last_name || props.person.last_name,
        gender:               parentForm.gender,
        birth_date_gregorian: parentForm.birth_date_gregorian || null,
        birth_date_hebrew:    parentForm.birth_date_hebrew || null,
      }
  router.post(`/people/${props.person.id}/parent`, payload, {
    onSuccess: () => { showAddParent.value = false },
    onFinish:  () => { savingParent.value = false },
  })
}

// ─── Add spouse ──────────────────────────────────────────
const showAddSpouse = ref(false)
const spouseTab     = ref('new')
const spouseSearch  = ref('')
const savingSpouse  = ref(false)
const spouseForm    = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', birth_date_hebrew: '',
  marriage_date_gregorian: '', marriage_date_hebrew: '', existing_id: null,
})

const filteredForSpouse = computed(() => {
  const q = spouseSearch.value.toLowerCase()
  const excludeIds = new Set([props.person.id, ...props.spouses.map(s => s.id)])
  return props.allPeople
    .filter(p => !excludeIds.has(p.id))
    .filter(p => !q || p.label.toLowerCase().includes(q))
    .slice(0, 8)
})

const canSubmitSpouse = computed(() => {
  if (spouseTab.value === 'new') return spouseForm.first_name && spouseForm.gender
  return !!spouseForm.existing_id
})

function submitSpouse() {
  savingSpouse.value = true
  if (spouseTab.value === 'existing') {
    router.post(`/people/${props.person.id}/spouse`, {
      spouse_id:               spouseForm.existing_id,
      marriage_date_gregorian: spouseForm.marriage_date_gregorian || null,
      marriage_date_hebrew:    spouseForm.marriage_date_hebrew    || null,
    }, {
      onSuccess: () => { showAddSpouse.value = false },
      onFinish:  () => { savingSpouse.value = false },
    })
  } else {
    router.post('/people', {
      first_name:              spouseForm.first_name,
      last_name:               spouseForm.last_name || '',
      gender:                  spouseForm.gender,
      birth_date_gregorian:    spouseForm.birth_date_gregorian    || null,
      birth_date_hebrew:       spouseForm.birth_date_hebrew       || null,
      marriage_date_gregorian: spouseForm.marriage_date_gregorian || null,
      marriage_date_hebrew:    spouseForm.marriage_date_hebrew    || null,
      spouse_id:               props.person.id,
    }, {
      onSuccess: () => { showAddSpouse.value = false },
      onFinish:  () => { savingSpouse.value = false },
    })
  }
}

// ─── Add sibling ─────────────────────────────────────────
const showAddSibling = ref(false)
const savingSibling  = ref(false)
const siblingForm    = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', birth_date_hebrew: '',
})

function openAddSibling() {
  siblingForm.first_name = ''; siblingForm.last_name = ''
  siblingForm.gender = ''; siblingForm.birth_date_gregorian = ''; siblingForm.birth_date_hebrew = ''
  showAddSibling.value = true
}

function submitSibling() {
  savingSibling.value = true
  router.post(`/people/${props.person.id}/sibling`, {
    first_name:           siblingForm.first_name,
    last_name:            siblingForm.last_name || props.person.last_name,
    gender:               siblingForm.gender,
    birth_date_gregorian: siblingForm.birth_date_gregorian || null,
    birth_date_hebrew:    siblingForm.birth_date_hebrew || null,
  }, {
    onSuccess: () => { showAddSibling.value = false },
    onFinish:  () => { savingSibling.value = false },
  })
}

// ─── Add child ───────────────────────────────────────────
const showAddChild = ref(false)
const savingChild  = ref(false)
const childForm    = reactive({
  first_name: '', last_name: '', gender: '', birth_date_gregorian: '', birth_date_hebrew: '',
  add_spouse_as_parent: false, co_parent_id: null,
})

function openAddChild() {
  childForm.first_name = ''; childForm.last_name = ''; childForm.gender = ''
  childForm.birth_date_gregorian = ''; childForm.birth_date_hebrew = ''
  childForm.add_spouse_as_parent = false; childForm.co_parent_id = null
  showAddChild.value = true
}

function submitChild() {
  savingChild.value = true
  const parentIds = [props.person.id]
  let explicit = false
  if (props.spouses.length === 1 && childForm.add_spouse_as_parent) {
    parentIds.push(props.spouses[0].id)
  } else if (props.spouses.length > 1 && childForm.co_parent_id) {
    // With several spouses the choice must be explicit, or the auto-merge would re-add the others
    parentIds.push(childForm.co_parent_id)
    explicit = true
  }
  router.post('/people', {
    first_name:           childForm.first_name,
    last_name:            childForm.last_name || props.person.last_name,
    gender:               childForm.gender,
    birth_date_gregorian: childForm.birth_date_gregorian || null,
    birth_date_hebrew:    childForm.birth_date_hebrew || null,
    parent_ids:           parentIds,
    explicit_parents:     explicit,
  }, {
    onSuccess: () => { showAddChild.value = false },
    onFinish:  () => { savingChild.value = false },
  })
}

// ─── Set a child's other parent (overrides spouse auto-merge) ─────────
const page              = usePage()
const isAdmin           = computed(() => page.props?.auth?.user?.role === 'admin')
const showChildParent   = ref(false)
const childParentTarget = ref(null)   // the child being edited
const childParentCoId   = ref(null)   // chosen co-parent (a spouse of this person), or null = sole parent
const savingChildParent = ref(false)

function openChildParent(child) {
  childParentTarget.value = child
  childParentCoId.value   = null
  showChildParent.value   = true
}

async function saveChildParent() {
  if (savingChildParent.value || !childParentTarget.value) return
  savingChildParent.value = true
  try {
    const ids = [props.person.id]
    if (childParentCoId.value) ids.push(childParentCoId.value)
    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res = await fetch(`/people/${childParentTarget.value.id}/set-parents`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body:    JSON.stringify({ parent_ids: ids }),
    })
    if (!res.ok) throw new Error()
    showChildParent.value = false
    router.reload({ only: ['children'] })
  } catch {
    alert('שגיאה בשמירת ההורה')
  } finally {
    savingChildParent.value = false
  }
}

// ─── Delete ──────────────────────────────────────────────
const showDeleteConfirm = ref(false)
const deleting          = ref(false)

function deletePerson() {
  deleting.value = true
  router.delete(`/people/${props.person.id}`, {
    onFinish: () => { deleting.value = false },
  })
}

// ─── Reorder children ────────────────────────────────────
const reorderingChildren = ref(false)
const childrenOrder      = ref([])
const dragIdx            = ref(null)
const dragOverIdx        = ref(null)
const savingOrder        = ref(false)

function startReorderChildren() {
  childrenOrder.value    = [...props.children]
  reorderingChildren.value = true
}

function cancelReorderChildren() {
  reorderingChildren.value = false
  dragIdx.value    = null
  dragOverIdx.value = null
}

function onDragStart(idx) { dragIdx.value = idx }
function onDragOver(idx)  { dragOverIdx.value = idx }
function onDragEnd()      { dragIdx.value = null; dragOverIdx.value = null }

function onDrop(targetIdx) {
  const from = dragIdx.value
  if (from === null || from === targetIdx) return
  const arr = [...childrenOrder.value]
  const [moved] = arr.splice(from, 1)
  arr.splice(targetIdx, 0, moved)
  childrenOrder.value = arr
  dragIdx.value    = null
  dragOverIdx.value = null
}

async function saveChildrenOrder() {
  if (savingOrder.value) return
  savingOrder.value = true
  try {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res = await fetch(`/people/${props.person.id}/reorder-children`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body:    JSON.stringify({ child_ids: childrenOrder.value.map(c => c.id) }),
    })
    if (!res.ok) throw new Error()
    reorderingChildren.value = false
    router.reload({ only: ['children'] })
  } catch {
    alert('שגיאה בשמירת הסדר')
  } finally {
    savingOrder.value = false
  }
}

// ─── Set profile from tagged photo ──────────────────────
const settingProfile = ref(null)
const profileSetMsg  = ref(null)

async function cropAndSetProfile(pt) {
  if (settingProfile.value) return
  settingProfile.value = pt.tag_id
  profileSetMsg.value  = null

  try {
    const blob = await cropFaceFromUrl(pt.url, pt)
    if (!blob) throw new Error()

    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const fd = new FormData()
    fd.append('profile_photo', blob, 'face.jpg')
    const res = await fetch(`/people/${props.person.id}/photo`, {
      method:  'POST',
      headers: { 'X-CSRF-TOKEN': token },
      body:    fd,
    })
    if (!res.ok && !res.redirected) throw new Error()

    photoPreview.value  = URL.createObjectURL(blob)
    profileSetMsg.value = { ok: true, text: 'תמונת הפרופיל עודכנה' }
  } catch {
    profileSetMsg.value = { ok: false, text: 'שגיאה — נסה/י שוב' }
  } finally {
    settingProfile.value = null
    setTimeout(() => { profileSetMsg.value = null }, 3500)
  }
}

function cropFaceFromUrl(url, rect) {
  return new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => {
      try {
        const nw = img.naturalWidth
        const nh = img.naturalHeight
        const sx = (rect.x_percent / 100) * nw
        const sy = (rect.y_percent / 100) * nh
        const sw = (rect.w_percent / 100) * nw
        const sh = (rect.h_percent / 100) * nh
        const canvas = document.createElement('canvas')
        canvas.width  = Math.round(sw)
        canvas.height = Math.round(sh)
        canvas.getContext('2d').drawImage(img, sx, sy, sw, sh, 0, 0, sw, sh)
        canvas.toBlob(resolve, 'image/jpeg', 0.92)
      } catch { resolve(null) }
    }
    img.onerror = () => resolve(null)
    img.src = url
  })
}

// ─── Helpers ─────────────────────────────────────────────
function initials(name) { return (name || '').split(' ').map(w => w[0]).join('').slice(0, 2) }
function formatDate(d)  {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}
</script>

<style scoped>
.show-page { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; font-family: 'Rubik', sans-serif; }

/* ─── Hero ─── */
.person-hero {
  border-radius: 20px; padding: 2rem; margin-bottom: 1.5rem;
  background: white; box-shadow: 0 4px 20px rgba(0,50,150,0.08);
  border-right: 6px solid #2d6be4;
}
.person-hero.female  { border-right-color: #8b5cf6; }
.person-hero.deceased { border-right-color: #9ca3af; }
.hero-content { display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap; }

.avatar-wrap {
  width: 110px; height: 110px; border-radius: 50%; overflow: hidden;
  background: #e8f0fe; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; position: relative; cursor: pointer;
  border: 3px solid #dbeafe;
}
.avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
.initials-large { font-size: 2.2rem; font-weight: 700; color: #2d6be4; }
.avatar-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem; opacity: 0; transition: opacity 0.2s; border-radius: 50%;
}
.avatar-wrap:hover .avatar-overlay { opacity: 1; }

.hero-info { flex: 1; min-width: 200px; }
.name-row { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
h1 { font-size: 1.8rem; color: #1a3a6b; margin: 0; }
.deceased-badge { background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.9rem; }
.meta-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
.chip { background: #f0f7ff; border: 1px solid #d1e5fb; border-radius: 20px; padding: 0.25rem 0.75rem; font-size: 0.85rem; color: #2d4a7a; }
.chip-gray { background: #f1f5f9; border-color: #e2e8f0; color: #64748b; }
.chip-invite { cursor: pointer; background: #eff6ff; border-color: #3b82f6; color: #2563eb; font-weight: 600; transition: background 0.15s; }
.chip-invite:hover:not(:disabled) { background: #dbeafe; }
.chip-invite:disabled { opacity: 0.6; cursor: default; }
.hebrew-date { opacity: 0.8; }
.bio-text { color: #4a5568; font-size: 0.95rem; line-height: 1.6; margin: 0; }

.hero-actions { display: flex; flex-direction: column; gap: 0.5rem; align-self: flex-start; }
.btn-edit {
  background: #e8f0fe; color: #2d6be4; padding: 0.5rem 1.2rem; border-radius: 8px;
  border: none; font-size: 0.9rem; font-weight: 500; cursor: pointer;
  font-family: 'Rubik', sans-serif; transition: background 0.2s;
}
.btn-edit:hover { background: #dbeafe; }
.btn-delete {
  background: none; border: 1.5px solid #fca5a5; color: #e74c3c;
  padding: 0.45rem 1.2rem; border-radius: 8px; font-size: 0.9rem;
  cursor: pointer; font-family: 'Rubik', sans-serif;
}

/* ─── Family grid ─── */
.family-grid { display: flex; flex-direction: column; gap: 1.25rem; }
.family-section { background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 10px rgba(0,50,150,0.06); }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
.section-header-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center; }
h2 { font-size: 1rem; color: #2d4a7a; margin: 0; font-weight: 600; }

.btn-add-inline {
  background: none; border: 1.5px dashed #2d6be4; color: #2d6be4;
  padding: 0.3rem 0.8rem; border-radius: 7px; font-size: 0.85rem;
  cursor: pointer; font-family: 'Rubik', sans-serif; transition: background 0.2s;
  text-decoration: none; display: inline-block;
}
.btn-add-inline:hover { background: #edf3ff; }

.family-cards { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.empty-family { color: #aab; font-size: 0.88rem; padding: 0.5rem 0; }

.mini-card {
  display: flex; flex-direction: column; align-items: center; gap: 0.4rem;
  text-decoration: none; color: #1a3a6b; font-size: 0.85rem;
  padding: 0.75rem; border-radius: 10px; background: #f8faff;
  border: 1px solid #e4eefb; transition: all 0.2s; min-width: 80px; text-align: center;
}
.mini-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,50,150,0.1); }
.mini-card.female { background: #fdf4ff; border-color: #e9d5ff; }
.mini-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #e8f0fe; display: flex; align-items: center; justify-content: center; }
.mini-avatar img { width: 100%; height: 100%; object-fit: cover; }
.mini-initials { font-size: 1rem; font-weight: 700; color: #2d6be4; }
.mini-marriage { font-size: 0.75rem; color: #7c5a8a; opacity: 0.85; }

/* ─── Co-parent picker on child cards ─── */
.mini-card-wrap { position: relative; display: inline-flex; }
.mini-card-wrap .mini-card { width: 100%; }
.btn-coparent {
  position: absolute; top: 4px; left: 4px;
  width: 24px; height: 24px; border-radius: 50%;
  border: 1px solid #e4eefb; background: #fff; cursor: pointer;
  font-size: 0.8rem; line-height: 1; padding: 0;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 1px 4px rgba(0,50,150,0.12); opacity: 0.75; transition: opacity 0.15s, transform 0.15s;
}
.btn-coparent:hover { opacity: 1; transform: scale(1.12); }
.hint-small { font-size: 0.78rem; color: #6b7c93; margin: 0.25rem 0 0.5rem; line-height: 1.4; }
.rel-select {
  width: 100%; padding: 0.5rem 0.6rem; border: 1px solid #d4e2f5; border-radius: 8px;
  font-size: 0.9rem; color: #1a3a6b; background: #fff;
}

/* ─── Reorder children ─── */
.btn-reorder        { border-color: #7c9ecc; color: #4a6fa5; }
.btn-reorder-save   { border-color: #22c55e; color: #16a34a; background: #f0fdf4; }
.btn-reorder-cancel { border-color: #f87171; color: #dc2626; }
.family-cards.reordering { gap: 0.75rem; }
.mini-card.draggable { cursor: grab; position: relative; border-style: dashed; user-select: none; }
.mini-card.draggable:active { cursor: grabbing; }
.mini-card.dragging  { opacity: 0.4; }
.mini-card.drag-over { border-color: #2d6be4; background: #e8f0fe; transform: scale(1.04); }
.drag-handle { position: absolute; top: 4px; right: 6px; font-size: 0.85rem; color: #9ab; line-height: 1; }

/* ─── Photos section ─── */
.photos-section { margin-top: 0; }
.photos-hint { font-size: 0.78rem; color: #94a3b8; margin: -0.5rem 0 0.75rem; }
.photos-grid { display: flex; gap: 0.75rem; flex-wrap: wrap; }

.photo-thumb-wrap {
  display: flex; flex-direction: column; gap: 0.35rem; width: 110px;
}
.photo-thumb-link {
  display: block; width: 110px; border-radius: 10px; overflow: hidden;
  text-decoration: none; border: 1px solid #e4eefb;
  transition: transform 0.2s, box-shadow 0.2s;
}
.photo-thumb-link:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,50,150,.1); }
.photo-thumb-link img { width: 100%; height: 90px; object-fit: cover; display: block; }
.photo-caption { display: block; font-size: 0.72rem; color: #2d4a7a; padding: 0.25rem 0.4rem; background: #f8faff; }

.btn-set-profile {
  width: 100%;
  padding: 0.3rem 0;
  font-size: 0.75rem;
  font-family: 'Rubik', sans-serif;
  border: 1.5px solid #2d6be4;
  border-radius: 7px;
  background: white;
  color: #2d6be4;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-set-profile:hover:not(:disabled) { background: #edf3ff; }
.btn-set-profile:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-set-profile.loading { border-color: #94a3b8; color: #94a3b8; }

.profile-set-msg {
  margin-top: 0.75rem; font-size: 0.85rem; padding: 0.5rem 0.75rem; border-radius: 8px;
}
.profile-set-msg.ok  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.profile-set-msg.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.photos-empty-action { margin-top: 0.5rem; text-align: center; padding: 0.5rem 0 1rem; }

/* ─── Modals ─── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal { background: white; border-radius: 18px; padding: 2rem; max-width: 460px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.18); max-height: 90vh; overflow-y: auto; }
.modal-wide { max-width: 620px; }
.modal h3 { margin: 0 0 1.25rem; color: #1a3a6b; font-size: 1.15rem; }
.modal-info { font-size: 0.88rem; color: #4a5568; margin-bottom: 1rem; background: #f0f7ff; padding: 0.5rem 0.75rem; border-radius: 7px; }

.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 130px; display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1rem; }
label { font-size: 0.85rem; color: #4a5568; font-weight: 500; }
input[type="text"], input[type="date"], input[type="email"], textarea, select {
  padding: 0.55rem 0.75rem; border: 1.5px solid #d1dce8; border-radius: 8px;
  font-size: 0.95rem; font-family: 'Rubik', sans-serif; direction: rtl; background: white;
}
input:focus, textarea:focus { outline: none; border-color: #2d6be4; }
input.is-error { border-color: #e74c3c; }
textarea { resize: vertical; }
.error-msg { color: #e74c3c; font-size: 0.8rem; }

.gender-toggle { display: flex; border: 1.5px solid #d1dce8; border-radius: 8px; overflow: hidden; }
.gender-toggle button { flex: 1; padding: 0.5rem; border: none; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #6b7a99; transition: all 0.2s; }
.gender-toggle button.active { background: #2d6be4; color: white; }

.deceased-section { margin-top: 0.5rem; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: #4a5568; }
.checkbox-label input { width: 16px; height: 16px; cursor: pointer; }

.tab-bar { display: flex; border: 1.5px solid #d1dce8; border-radius: 9px; overflow: hidden; margin-bottom: 1.25rem; }
.tab { flex: 1; padding: 0.55rem; border: none; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #6b7a99; transition: all 0.2s; }
.tab.active { background: #2d6be4; color: white; font-weight: 600; }

.existing-list { display: flex; flex-direction: column; gap: 0.35rem; max-height: 200px; overflow-y: auto; margin-top: 0.5rem; }
.existing-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border: 1.5px solid #e4eefb; border-radius: 9px; background: white; cursor: pointer; font-family: 'Rubik', sans-serif; font-size: 0.9rem; color: #2d4a7a; text-align: right; transition: all 0.15s; width: 100%; }
.existing-item:hover { border-color: #2d6be4; background: #edf3ff; }
.existing-item.selected { border-color: #2d6be4; background: #dbeafe; font-weight: 600; }
.mini-initials-sm { width: 28px; height: 28px; border-radius: 50%; background: #e8f0fe; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #2d6be4; flex-shrink: 0; }

.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
.btn-cancel { background: white; border: 1.5px solid #d1dce8; color: #4a5568; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; }
.btn-primary-modal { background: #2d6be4; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; font-weight: 600; }
.btn-primary-modal:disabled { opacity: 0.55; cursor: not-allowed; }

.modal-warning { color: #e74c3c; font-size: 0.88rem; }
.btn-delete-confirm { background: #e74c3c; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 8px; cursor: pointer; font-family: 'Rubik', sans-serif; font-weight: 600; }
.btn-delete-confirm:disabled { opacity: 0.6; cursor: not-allowed; }

/* ─── Photo modal ─── */
.photo-modal-row { display: flex; gap: 1.5rem; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; }
.photo-preview-wrap { flex-shrink: 0; }
.photo-preview { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #dbeafe; }
.photo-placeholder {
  width: 90px; height: 90px; border-radius: 50%; background: #e8f0fe;
  display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; color: #2d6be4;
}
.photo-controls { display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; }
.btn-choose-photo {
  display: inline-block; cursor: pointer; padding: 0.5rem 1rem;
  background: #f0f7ff; border: 1.5px solid #2d6be4; color: #2d6be4;
  border-radius: 8px; font-size: 0.9rem; font-family: 'Rubik', sans-serif;
}
.btn-choose-photo:hover { background: #dbeafe; }
.photo-hint { font-size: 0.78rem; color: #94a3b8; margin: 0; }
</style>
