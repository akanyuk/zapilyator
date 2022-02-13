scroll16
		
S16CNTP	ld a, #0f : inc a : and #0f : ld (S16CNTP + 1), a
		or a : jr nz, p1scroll
S16CNT	ld hl, SCROLL_TEXT - 1 
		inc hl : ld (S16CNT + 1), hl

		ld a, (hl) : or a : jp nz, 1f
		
		; restart scroll
		ld hl, SCROLL_TEXT - 1 
		ld (S16CNT + 1), hl
		
1		call drw_sym16x16
		
		; скролл на 1 пикс.
p1scroll
		; адрес экрана строки скролла
		ld de, _scroll_address
		ld hl, SCROLL_BUFF + 1
		ld a, #10 : ld (p1sA + 1), a

p1s1	scf : ccf ; reset carry
	
		rl (hl) : dec hl : rl (hl)
		inc hl : inc hl : inc hl

		ex de, hl
		push hl
		dup 32
		rl (hl) : dec hl
		edup
		pop hl
		call downHL
		ex de, hl
p1sA	ld a, #00 : dec a : ld (p1sA + 1), a : ret z : jp p1s1

drw_sym16x16	
		sub #20 : cp #60 : jr C, $+4 : sub #60	; fix chr for cutted font
			
		ld H, #00 : ld L, A
		DUP 5
		add hl, hl ; HL = HL*32
		EDUP
			
		ld bc, FONT16X16
		add hl, bc	; јдрес спрайта

		ld (_ds16SP + 1), SP
		ld sp, hl ; стек указывает на начало спрайта
		
		ld hl, SCROLL_BUFF ; куда переносим

		; Ќепосредственно перенос спрайта
		DUP 16
		pop bc : ld (hl), c : inc hl : ld (hl), b : inc hl
		EDUP
_ds16SP	ld SP, #0000	
		ret

downHL	inc h : ld a,h : and #07 : ret nz : ld a,l : sub #e0 : ld l,a : sbc a,a : and #f8 : add a,h : ld h,a : ret
	
SCROLL_TEXT
		incbin "res/scroll"
		db #00
FONT16X16	
		incbin "res/16x16font"
SCROLL_BUFF	