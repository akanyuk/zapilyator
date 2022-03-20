	define SNA_FILENAME "test.sna"
	define TRD_FILENAME "test.trd"
	define BIN_FILENAME "test.bin"

	device zxspectrum128

	%if_music%define _PLAY_MUSIC
	%if_int_flow%define _INT_FLOW

	page 0
		
	org #6000
page0s	di : ld sp, $-2
	ld a, #10 : call setPage

	ifdef _PLAY_MUSIC
	call PT3PLAY
	endif
		
	ld a,#5c, i,a, hl,interr, (#5cff),hl : im 2 : ei

	; main flow
%main_flow%
	jr $

	;int flow
	ifdef _INT_FLOW
	; Обработчик перываний. Вызывается каждый int
intFlow
	ld hl, INT_DATA
ifMain	ld a, (hl) : inc hl
	cp #ff : ret z	; end of flow
	or a : jr z, ifSkip7
	ld a, (hl) : inc hl : call setPage
	ld a, (hl) : inc hl : ld (ifCall + 1), a
	ld a, (hl) : inc hl : ld (ifCall + 2), a
	push hl 
ifCall	call #0000
	pop hl
	dec (hl) : ld a, (hl) : inc hl : cp #ff : jr nz, ifSkip3
	dec (hl) : ld a, (hl) : inc hl : cp #ff : jr nz, ifSkip2
	; закончили эффект
	ld de, #0006 : sbc hl, de : xor a : ld (hl), a : add hl, de
	inc hl
	ld a, (hl) : inc hl : cp #ff : jr z, ifMain ; после эффекта ничего
	push hl
	call intFlowPush
	pop hl
	jr ifMain
ifSkip7	dup 4 : inc hl : edup
ifSkip3	inc hl	
ifSkip2	inc hl
ifSkip1	inc hl
	jr ifMain

	; Обработчик таймлайна. Вызывается после каждой смены паттерна
timelineUpdate
	; остановить процедуры, если stop_pattern == CUR_PATTERN
	ld hl, CUR_PATTERN
	ld ix, INT_DATA
	ld de, #0008
tuStage1c	ld a, (ix + 0) : cp #ff : jr z, tuStage2
	or a : jr z, tuStage1e
	ld a, (ix + 6) : cp (hl) : jr nz, tuStage1e

	; останавливаем
	xor a : ld (ix + 0), a
tuStage1e	add ix, de
	jr tuStage1c

tuStage2	; сжать main cycle (выкинуть остановленные, остальное уплотнить)
	ld hl, INT_DATA + 8
	ld de, INT_DATA
	ld bc, #0008
tuStage2c	ld a, (hl) : cp #ff : jr z, tuStage3
	ld a, (de) : or a : jr nz, tuStage2sk
	ld a, (hl) : or a : jr z, tuStage2sk2
	; переносим
	push bc : ldir : pop bc	
	jr tuStage2c
tuStage2sk	ex de, hl : add hl, bc : ex de, hl 
tuStage2sk2	add hl, bc : jr tuStage2c

tuStage3	; добавить процедуры, если start_pattern == CUR_PATTERN
	ld ix, CUR_PATTERN
	ld hl, TIMELINE
	ld de, #0008
tuStage3c	ld a, (hl) : cp #ff : ret z
	cp (ix + 0) : jr nz, tuStage3e
	push hl, de
	call intFlowPushHL
	pop de, hl
tuStage3e	add hl, de : jr tuStage3c

	; Добавляем процедуру в обработчик перываний
	; a - номер процедуры в TIMELINE
intFlowPush
	ld h, #00 : ld l, a
	dup 3 : add hl, hl : edup
	ld de, TIMELINE
	add hl, de
intFlowPushHL
	ld de, INT_DATA
	ld bc, #0008
ifpCycle	ld a, (de) 
	or a : jr z, ifpDo
	cp #ff : jr z, ifpDo
	dup 8 : inc de : edup
	jr ifpCycle
ifpDo	; заносим
	push hl
	ld a, #01 : ld (hl), a ; маркер `run`
	ldir
	pop hl
	ld a, #fe : ld (hl), a ; защита от повторного добавления
	ret

INT_DATA	block 16*08, #ff
TIMELINE
%timeline%
	db #ff		; end of TIMELINE

%functions%	
	endif


CUR_PAGE	db #00
setPage	ld (CUR_PAGE), a : ld bc,#7ffd : out(c), a : ret

	ifdef _PLAY_MUSIC
CUR_PATTERN	db #ff
_curPattern	db #ff	
	align #100
PT3PLAY	include "sources/PTxPlay.asm"
	incbin "res/music"
	else
	; always 0 pattern
CUR_PATTERN	db #00
	endif


interr	di
	push af,bc,de,hl,ix,iy
	exx : ex af, af'
	push af,bc,de,hl,ix,iy
	; save current page
	ld a, (CUR_PAGE)
	ld (_CP_REST + 1), a

	ifdef _INT_FLOW
	call intFlow
	endif

	ifdef _PLAY_MUSIC
	call PT3PLAY + 5
	ld a, (PT3PLAY + 11)
	ld hl, _curPattern 
	cp (hl) : jr z, 1f
	ld (hl), a
	ld hl, CUR_PATTERN : inc (HL)
1	
	endif

	ifdef _INT_FLOW
	call timelineUpdate
	endif

	; restore page
_CP_REST	ld a, #00 : call setPage
	pop iy,ix,hl,de,bc,af
	exx : ex af, af'
	pop iy,ix,hl,de,bc,af
	ei
	ret

%data_flow%
	include "sources/builder.asm"